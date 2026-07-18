<?php

namespace App\Http\Controllers;

use App\Models\DashboardRunStatus;
use App\Models\GeneratedDocument;
use App\Models\Job;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LegacyDashboardApiController extends Controller
{
    public function __invoke(Request $request): JsonResponse|StreamedResponse
    {
        return match ($request->query('action', 'list')) {
            'list' => $this->listJobs($request),
            'meta' => $this->meta($request),
            'status' => $this->updateStatus($request),
            'generate' => $this->requestGenerate($request),
            'generated' => $this->markGenerated($request),
            'application' => $this->updateApplication($request),
            'upsert' => $this->upsert($request),
            'run-status' => $this->updateRunStatus($request),
            'file' => $this->serveFile($request),
            default => response()->json(['error' => 'Unknown action'], 404),
        };
    }

    private function listJobs(Request $request): JsonResponse
    {
        $this->requireAuthenticatedDashboard($request);

        return response()->json(Job::query()
            ->orderByRaw("FIELD(overall_recommendation, 'Apply', 'Maybe', 'Pass')")
            ->orderByDesc('career_fit_score')
            ->orderByDesc('life_fit_score')
            ->orderByDesc('last_seen')
            ->orderBy('company')
            ->get());
    }

    private function meta(Request $request): JsonResponse
    {
        $this->requireAuthenticatedDashboard($request);

        $defaults = collect(['last_sync', 'last_discovery', 'last_generate'])
            ->mapWithKeys(fn (string $name): array => [$name => [
                'run_name' => $name,
                'last_run_at' => '',
                'status' => 'Not run',
                'details' => '',
            ]]);

        return response()->json([
            'runs' => $defaults->merge(DashboardRunStatus::query()->get()->keyBy('run_name'))->all(),
            'server_time' => now()->toIso8601String(),
        ]);
    }

    private function updateStatus(Request $request): JsonResponse
    {
        $this->requireAuthenticatedDashboard($request);
        $data = $request->json()->all();
        $job = Job::query()->findOrFail((int) ($data['id'] ?? 0));
        $status = trim((string) ($data['status'] ?? ''));

        if (! in_array($status, Job::WORKFLOW_STATUSES, true)) {
            return response()->json(['error' => 'Invalid id or status'], 400);
        }

        $job->update(['status' => $status]);
        $job->events()->create(['event_type' => 'status', 'event_note' => $status]);

        return response()->json(['ok' => true, 'id' => $job->id, 'status' => $status]);
    }

    private function requestGenerate(Request $request): JsonResponse
    {
        $this->requireAuthenticatedDashboard($request);
        $job = Job::query()->findOrFail((int) ($request->json('id') ?? 0));
        $note = 'Custom application package generation requested from dashboard.';

        $job->update([
            'status' => 'Generate Requested',
            'notes' => $this->consolidateNotes($job->notes, $note),
        ]);
        $job->events()->create(['event_type' => 'generate_requested', 'event_note' => $note]);

        return response()->json(['ok' => true, 'id' => $job->id, 'status' => 'Generate Requested']);
    }

    private function markGenerated(Request $request): JsonResponse
    {
        $this->requireApiToken($request);
        $data = $request->json()->all();
        $job = Job::query()->findOrFail((int) ($data['id'] ?? 0));
        $resume = trim((string) ($data['resume_file'] ?? ''));
        $cover = trim((string) ($data['cover_letter_file'] ?? ''));

        if ($resume === '' || $cover === '') {
            return response()->json(['error' => 'id, resume_file, and cover_letter_file are required'], 400);
        }

        $note = trim((string) ($data['note'] ?? 'Custom application package generated. Human review required before submission.'));
        $job->update([
            'status' => 'Ready for Review',
            'resume_file' => $resume,
            'cover_letter_file' => $cover,
            'resume_lane' => trim((string) ($data['resume_lane'] ?? '')),
            'review_summary_file' => trim((string) ($data['review_summary_file'] ?? '')),
            'generated_docs_summary' => trim((string) ($data['generated_docs_summary'] ?? '')),
            'notes' => $this->consolidateNotes($job->notes, $note),
        ]);

        $this->syncDocumentReferences($job);
        $job->events()->create(['event_type' => 'generated', 'event_note' => $note]);

        return response()->json(['ok' => true, 'id' => $job->id, 'status' => 'Ready for Review']);
    }

    private function updateApplication(Request $request): JsonResponse
    {
        if ($this->isQueueAgent($request)) {
            $this->requireApiToken($request);
        } else {
            $this->requireAuthenticatedDashboard($request);
        }

        $data = $request->json()->all();
        $job = Job::query()->findOrFail((int) ($data['id'] ?? 0));
        $status = trim((string) ($data['application_status'] ?? ''));

        if (! in_array($status, Job::APPLICATION_STATUSES, true)) {
            return response()->json(['error' => 'Invalid id or application status'], 400);
        }

        $note = trim((string) ($data['note'] ?? 'Application queue updated. Human review required before final submission.'));
        $job->update([
            'application_status' => $status,
            'application_missing_fields' => trim((string) ($data['missing_fields'] ?? $data['application_missing_fields'] ?? '')),
            'application_warnings' => trim((string) ($data['application_warnings'] ?? '')),
            'application_screenshot' => trim((string) ($data['application_screenshot'] ?? '')),
            'application_review_file' => trim((string) ($data['application_review_file'] ?? '')),
            'application_last_action' => trim((string) ($data['application_last_action'] ?? '')),
            'application_ready_at' => in_array($status, ['Ready for Review', 'Needs Manual Review'], true) ? now() : null,
            'resume_file' => $this->preferNew($data['resume_file'] ?? null, $job->resume_file),
            'cover_letter_file' => $this->preferNew($data['cover_letter_file'] ?? null, $job->cover_letter_file),
            'resume_lane' => $this->preferNew($data['resume_lane'] ?? null, $job->resume_lane),
            'status' => $status === 'Submitted' ? 'Submitted' : (in_array($status, ['Ready for Review', 'Needs Manual Review'], true) ? $status : $job->status),
            'notes' => $this->consolidateNotes($job->notes, $note),
        ]);

        $this->syncDocumentReferences($job);
        $job->events()->create(['event_type' => 'application_queue', 'event_note' => trim($status.' '.$note)]);

        return response()->json(['ok' => true, 'id' => $job->id, 'application_status' => $status]);
    }

    private function upsert(Request $request): JsonResponse
    {
        $this->requireApiToken($request);
        $data = $request->json()->all();
        $company = trim((string) ($data['company'] ?? ''));
        $role = trim((string) ($data['role'] ?? ''));
        $url = trim((string) ($data['url'] ?? ''));

        if ($company === '' || $role === '') {
            return response()->json(['error' => 'company and role are required'], 400);
        }

        $urlHash = Job::urlHash($url, $company, $role);
        $existing = Job::query()->where('url_hash', $urlHash)->first();
        $score = (int) ($data['match_score'] ?? 0);
        $status = trim((string) ($data['status'] ?? ''));

        if (! in_array($status, Job::WORKFLOW_STATUSES, true)) {
            $status = $score >= 80 ? 'Apply Soon' : 'Sourced - Needs Review';
        }

        if ($existing && in_array($existing->status, ['Interested', 'Generate Requested', 'Pass', 'Ready for Review', 'Applied', 'Submitted'], true)) {
            $status = $existing->status;
        }

        $job = Job::query()->updateOrCreate(['url_hash' => $urlHash], [
            'company' => $company,
            'role' => $role,
            'url' => $url,
            'salary' => trim((string) ($data['salary'] ?? '')),
            'remote_status' => trim((string) ($data['remote_status'] ?? '')),
            'match_score' => $score,
            'career_fit_score' => (int) ($data['career_fit_score'] ?? $score),
            'life_fit_score' => (int) ($data['life_fit_score'] ?? 0),
            'overall_recommendation' => trim((string) ($data['overall_recommendation'] ?? '')),
            'why_considering' => trim((string) ($data['why_considering'] ?? '')),
            'tradeoffs_watch_outs' => trim((string) ($data['tradeoffs_watch_outs'] ?? '')),
            'local_exception_reason' => trim((string) ($data['local_exception_reason'] ?? '')),
            'commute_notes' => trim((string) ($data['commute_notes'] ?? '')),
            'benefits_pension_notes' => trim((string) ($data['benefits_pension_notes'] ?? '')),
            'resume_angle' => trim((string) ($data['resume_angle'] ?? '')),
            'source_lane' => trim((string) ($data['source_lane'] ?? '')),
            'executive_watch' => ! empty($data['executive_watch']),
            'status' => $status,
            'first_seen' => $existing?->first_seen ?? today(),
            'last_seen' => today(),
            'times_seen' => $existing ? $existing->times_seen + 1 : 1,
            'days_on_market' => $existing?->first_seen ? $existing->first_seen->diffInDays(today()) + 1 : 1,
            'source' => trim((string) ($data['source'] ?? '')),
            'notes' => $this->consolidateNotes($existing?->notes, trim((string) ($data['notes'] ?? ''))),
            'description' => $this->preferNew($data['description'] ?? null, $existing?->description),
            'resume_file' => $this->preferNew($data['resume_file'] ?? null, $existing?->resume_file),
            'cover_letter_file' => $this->preferNew($data['cover_letter_file'] ?? null, $existing?->cover_letter_file),
            'resume_lane' => $this->preferNew($data['resume_lane'] ?? null, $existing?->resume_lane),
            'application_status' => $this->preferNew($data['application_status'] ?? null, $existing?->application_status ?: 'Not Started'),
            'application_missing_fields' => $this->preferNew($data['missing_fields'] ?? $data['application_missing_fields'] ?? null, $existing?->application_missing_fields),
            'application_warnings' => $this->preferNew($data['application_warnings'] ?? null, $existing?->application_warnings),
            'application_screenshot' => $this->preferNew($data['application_screenshot'] ?? null, $existing?->application_screenshot),
            'application_review_file' => $this->preferNew($data['application_review_file'] ?? null, $existing?->application_review_file),
            'application_last_action' => $this->preferNew($data['application_last_action'] ?? null, $existing?->application_last_action),
        ]);

        $this->syncDocumentReferences($job);

        return response()->json(['ok' => true, 'id' => $job->id]);
    }

    private function updateRunStatus(Request $request): JsonResponse
    {
        $this->requireApiToken($request);
        $allowed = ['last_sync', 'last_discovery', 'last_generate'];
        $updated = 0;

        foreach ($allowed as $name) {
            $row = $request->json($name);

            if (! is_array($row)) {
                continue;
            }

            DashboardRunStatus::query()->updateOrCreate(['run_name' => $name], [
                'last_run_at' => trim((string) ($row['last_run_at'] ?? '')),
                'status' => trim((string) ($row['status'] ?? '')),
                'details' => trim((string) ($row['details'] ?? '')),
            ]);
            $updated++;
        }

        return response()->json(['ok' => true, 'updated' => $updated]);
    }

    private function serveFile(Request $request): StreamedResponse|JsonResponse
    {
        $this->requireAuthenticatedDashboard($request);
        $path = str_replace('\\', '/', trim((string) $request->query('path', '')));

        if ($path === '' || str_contains($path, "\0") || preg_match('#(^|/)\.\.(/|$)#', $path)) {
            return response()->json(['error' => 'Invalid file path'], 400);
        }

        $document = GeneratedDocument::query()
            ->where('v1_reference', $path)
            ->orWhere('stored_path', 'generated-documents/'.$path)
            ->first();

        $storedPath = $document?->stored_path ?: 'generated-documents/'.$path;

        if (! Storage::disk('local')->exists($storedPath)) {
            return response()->json(['error' => 'Generated file was not found. Run the V2 import with --copy-files.'], 404);
        }

        return Storage::disk('local')->download($storedPath);
    }

    private function syncDocumentReferences(Job $job): void
    {
        foreach ([
            'resume' => $job->resume_file,
            'cover_letter' => $job->cover_letter_file,
            'review_summary' => $job->review_summary_file,
            'application_review' => $job->application_review_file,
            'application_screenshot' => $job->application_screenshot,
        ] as $type => $reference) {
            $reference = trim((string) $reference);

            if ($reference === '') {
                continue;
            }

            GeneratedDocument::query()->updateOrCreate([
                'job_id' => $job->id,
                'document_type' => $type,
                'v1_reference' => $reference,
            ]);
        }
    }

    private function requireAuthenticatedDashboard(Request $request): void
    {
        abort_unless($request->user(), 401, 'Dashboard login required.');
    }

    private function requireApiToken(Request $request): void
    {
        $expected = (string) config('jobsearch.api_token');

        if ($expected === '') {
            return;
        }

        abort_unless(hash_equals($expected, (string) $request->header('X-API-Token')), 403, 'Invalid API token.');
    }

    private function isQueueAgent(Request $request): bool
    {
        return str_contains((string) $request->userAgent(), 'CodexJobApplicationQueue');
    }

    private function preferNew(mixed $new, mixed $existing): mixed
    {
        $trimmed = trim((string) $new);

        return $trimmed !== '' ? $trimmed : $existing;
    }

    private function consolidateNotes(?string ...$values): string
    {
        $seen = [];
        $parts = [];

        foreach ($values as $value) {
            foreach (preg_split('/[;\r\n]+/', (string) $value) ?: [] as $fragment) {
                $text = trim(preg_replace('/\s+/', ' ', $fragment) ?: '');

                if ($text === '') {
                    continue;
                }

                $key = trim(preg_replace('/[^a-z0-9]+/', ' ', strtolower($text)) ?: '');

                if ($key !== '' && ! isset($seen[$key])) {
                    $seen[$key] = true;
                    $parts[] = $text;
                }
            }
        }

        return implode('; ', $parts);
    }
}
