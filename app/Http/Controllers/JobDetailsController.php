<?php

namespace App\Http\Controllers;

use App\Models\GeneratedDocument;
use App\Models\Job;
use App\Models\UserPreference;
use App\Services\JobPriorityScorer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class JobDetailsController extends Controller
{
    public function show(Request $request, Job $job, JobPriorityScorer $scorer): JsonResponse
    {
        abort_unless($job->user_id === $request->user()->id, 404);
        Gate::forUser($request->user())->authorize('view', $job);

        $job->load([
            'applications' => fn ($query) => $query
                ->latest('updated_at')
                ->select(['id', 'user_id', 'job_id', 'status', 'applied_at', 'last_action_at', 'last_action', 'missing_fields', 'warnings', 'created_at', 'updated_at']),
            'documents' => fn ($query) => $query
                ->latest()
                ->select(['id', 'user_id', 'job_id', 'generated_document_id', 'document_type', 'display_filename', 'original_filename', 'mime_type', 'size_bytes', 'created_at']),
            'events' => fn ($query) => $query
                ->latest()
                ->limit(25)
                ->select(['id', 'user_id', 'job_id', 'event_type', 'event_note', 'created_at']),
            'generatedDocuments' => fn ($query) => $query
                ->latest()
                ->select(['id', 'user_id', 'job_id', 'document_type', 'v1_reference', 'stored_path', 'mime_type', 'size_bytes', 'created_at']),
            'jobNotes' => fn ($query) => $query
                ->latest()
                ->select(['id', 'user_id', 'job_id', 'body_markdown', 'source', 'created_at']),
            'operations' => fn ($query) => $query
                ->latest()
                ->limit(20)
                ->select(['id', 'user_id', 'job_id', 'operation_type', 'status', 'metadata', 'failure_reason', 'queued_at', 'started_at', 'finished_at']),
        ]);

        /** @var UserPreference|null $preferences */
        $preferences = $request->user()->preferences()->first();

        return response()->json([
            'id' => $job->id,
            'company' => $job->company,
            'role' => $job->role,
            'url' => $job->url,
            'salary' => $job->salary,
            'remote_status' => $job->remote_status,
            'priority_score' => $scorer->score($job, $preferences),
            'match_score' => $job->match_score,
            'career_fit_score' => $job->career_fit_score,
            'life_fit_score' => $job->life_fit_score,
            'overall_recommendation' => $job->overall_recommendation,
            'status' => $job->status,
            'application_status' => $job->application_status,
            'why_considering' => $job->why_considering,
            'tradeoffs_watch_outs' => $job->tradeoffs_watch_outs,
            'local_exception_reason' => $job->local_exception_reason,
            'commute_notes' => $job->commute_notes,
            'benefits_pension_notes' => $job->benefits_pension_notes,
            'resume_angle' => $job->resume_angle,
            'notes' => $job->notes,
            'description' => $job->description,
            'generated_docs_summary' => $job->generated_docs_summary,
            'score_explanation' => $scorer->explanation($job, $preferences),
            'documents' => $job->documents->map(fn ($document): array => [
                'id' => $document->id,
                'document_type' => $document->document_type,
                'display_filename' => $document->display_filename,
                'mime_type' => $document->mime_type,
                'size_bytes' => $document->size_bytes,
                'created_at' => $document->created_at?->toIso8601String(),
                'download_url' => route('dashboard.documents.download', $document),
            ])->values(),
            'generated_documents' => $job->generatedDocuments->map(fn (GeneratedDocument $document): array => [
                'id' => $document->id,
                'document_type' => $document->document_type,
                'display_filename' => $this->friendlyName($document->v1_reference ?: $document->stored_path, $document->document_type),
                'mime_type' => $document->mime_type,
                'size_bytes' => $document->size_bytes,
                'created_at' => $document->created_at?->toIso8601String(),
                'download_url' => route('dashboard.generated-documents.download', $document),
            ])->values(),
            'job_notes' => $job->jobNotes->map(fn ($note): array => [
                'id' => $note->id,
                'body_markdown' => $note->body_markdown,
                'source' => $note->source,
                'created_at' => $note->created_at?->toIso8601String(),
            ])->values(),
            'applications' => $job->applications->map(fn ($application): array => [
                'id' => $application->id,
                'status' => $application->status,
                'applied_at' => $application->applied_at?->toIso8601String(),
                'last_action_at' => $application->last_action_at?->toIso8601String(),
                'last_action' => $application->last_action,
                'missing_fields' => $application->missing_fields,
                'warnings' => $application->warnings,
                'created_at' => $application->created_at?->toIso8601String(),
                'updated_at' => $application->updated_at?->toIso8601String(),
            ])->values(),
            'operations' => $job->operations->map(fn ($operation): array => [
                'id' => $operation->id,
                'operation_type' => $operation->operation_type,
                'status' => $operation->status,
                'failure_reason' => $operation->failure_reason,
                'queued_at' => $operation->queued_at?->toIso8601String(),
                'started_at' => $operation->started_at?->toIso8601String(),
                'finished_at' => $operation->finished_at?->toIso8601String(),
            ])->values(),
            'events' => $job->events->map(fn ($event): array => [
                'id' => $event->id,
                'event_type' => $event->event_type,
                'event_note' => $event->event_note,
                'created_at' => $event->created_at?->toIso8601String(),
            ])->values(),
        ]);
    }

    private function friendlyName(?string $value, string $fallback): string
    {
        $normalized = str_replace('\\', '/', trim((string) $value));
        $name = basename($normalized);

        return $name !== '' ? $name : str($fallback)->headline()->toString();
    }
}
