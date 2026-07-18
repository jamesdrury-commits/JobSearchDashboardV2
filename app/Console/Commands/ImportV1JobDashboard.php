<?php

namespace App\Console\Commands;

use App\Models\DashboardRunStatus;
use App\Models\DataImportRun;
use App\Models\GeneratedDocument;
use App\Models\Job;
use App\Models\JobEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ImportV1JobDashboard extends Command
{
    protected $signature = 'jobsearch:import-v1
        {--dry-run : Read and validate V1 data without writing to V2}
        {--copy-files : Copy V1 generated files into V2 private storage}
        {--limit= : Limit imported jobs for testing}';

    protected $description = 'Import V1 Job Search Assistant dashboard data into the isolated V2 database.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $copyFiles = (bool) $this->option('copy-files');
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;

        $run = DataImportRun::query()->create([
            'mode' => $dryRun ? 'dry-run' : ($copyFiles ? 'copy-files' : 'database-only'),
            'status' => 'started',
            'started_at' => now(),
            'metadata' => [
                'v1_connection' => config('database.connections.v1_import.database'),
                'v2_database' => config('database.connections.'.config('database.default').'.database'),
                'copy_files' => $copyFiles,
                'limit' => $limit,
            ],
        ]);

        try {
            $query = DB::connection('v1_import')->table('jobs')->orderBy('id');

            if ($limit) {
                $query->limit($limit);
            }

            $rows = $query->get();
            $run->update(['jobs_seen' => $rows->count()]);
            $this->info("Read {$rows->count()} V1 job row(s).");

            if (! $dryRun) {
                DB::transaction(function () use ($rows, $copyFiles, $run): void {
                    $this->importJobs($rows, $copyFiles, $run);
                    $this->importEvents($run);
                    $this->importRunStatuses();
                });
            } else {
                foreach ($rows as $row) {
                    $this->validateJobRow((array) $row);
                }
            }

            $run->update([
                'status' => 'completed',
                'finished_at' => now(),
            ]);

            $this->info($dryRun ? 'Dry run completed without writing V2 rows.' : 'V1 import completed.');

            return self::SUCCESS;
        } catch (Throwable $throwable) {
            $run->update([
                'status' => 'failed',
                'errors_count' => $run->errors_count + 1,
                'finished_at' => now(),
                'metadata' => array_merge($run->metadata ?? [], ['fatal_error' => $throwable->getMessage()]),
            ]);

            $this->error($throwable->getMessage());

            return self::FAILURE;
        }
    }

    private function importJobs(iterable $rows, bool $copyFiles, DataImportRun $run): void
    {
        $imported = 0;
        $documents = 0;

        foreach ($rows as $row) {
            try {
                $payload = $this->mapJobRow((array) $row);
                $job = Job::query()->updateOrCreate(
                    ['v1_job_id' => $payload['v1_job_id']],
                    $payload,
                );

                $documents += $this->recordDocumentReferences($job, $copyFiles);
                $imported++;
            } catch (Throwable $throwable) {
                $this->recordRowError($run, 'jobs', (string) ($row->id ?? ''), $throwable->getMessage(), (array) $row);
            }
        }

        $run->update([
            'jobs_imported' => $imported,
            'documents_imported' => $documents,
            'errors_count' => $run->rowErrors()->count(),
        ]);
    }

    private function importEvents(DataImportRun $run): void
    {
        $eventsImported = 0;

        foreach (DB::connection('v1_import')->table('job_events')->orderBy('id')->get() as $row) {
            try {
                $job = Job::query()->where('v1_job_id', $row->job_id)->first();

                if (! $job) {
                    continue;
                }

                JobEvent::query()->updateOrCreate([
                    'v1_event_id' => $row->id,
                ], [
                    'job_id' => $job->id,
                    'event_type' => (string) $row->event_type,
                    'event_note' => $row->event_note,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->created_at ?? now(),
                ]);
                $eventsImported++;
            } catch (Throwable $throwable) {
                $this->recordRowError($run, 'job_events', (string) ($row->id ?? ''), $throwable->getMessage(), (array) $row);
            }
        }

        $run->update([
            'events_imported' => $eventsImported,
            'errors_count' => $run->rowErrors()->count(),
        ]);
    }

    private function importRunStatuses(): void
    {
        foreach (DB::connection('v1_import')->table('dashboard_run_status')->get() as $row) {
            DashboardRunStatus::query()->updateOrCreate([
                'run_name' => (string) $row->run_name,
            ], [
                'last_run_at' => (string) ($row->last_run_at ?? ''),
                'status' => (string) ($row->status ?? ''),
                'details' => $row->details,
                'updated_at' => $row->updated_at ?? now(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function mapJobRow(array $row): array
    {
        $company = trim((string) ($row['company'] ?? ''));
        $role = trim((string) ($row['role'] ?? ''));

        if ($company === '' || $role === '') {
            throw new \InvalidArgumentException('Job row is missing company or role.');
        }

        return [
            'v1_job_id' => (int) $row['id'],
            'company' => $company,
            'role' => $role,
            'url' => $row['url'] ?? '',
            'url_hash' => (string) ($row['url_hash'] ?? Job::urlHash($row['url'] ?? '', $company, $role)),
            'salary' => (string) ($row['salary'] ?? ''),
            'remote_status' => (string) ($row['remote_status'] ?? ''),
            'match_score' => (int) ($row['match_score'] ?? 0),
            'career_fit_score' => (int) ($row['career_fit_score'] ?? $row['match_score'] ?? 0),
            'life_fit_score' => (int) ($row['life_fit_score'] ?? 0),
            'overall_recommendation' => (string) ($row['overall_recommendation'] ?? ''),
            'why_considering' => $row['why_considering'] ?? null,
            'tradeoffs_watch_outs' => $row['tradeoffs_watch_outs'] ?? null,
            'local_exception_reason' => $row['local_exception_reason'] ?? null,
            'commute_notes' => $row['commute_notes'] ?? null,
            'benefits_pension_notes' => $row['benefits_pension_notes'] ?? null,
            'resume_angle' => $row['resume_angle'] ?? null,
            'source_lane' => (string) ($row['source_lane'] ?? ''),
            'executive_watch' => (bool) ($row['executive_watch'] ?? false),
            'status' => (string) ($row['status'] ?? 'Sourced - Needs Review'),
            'first_seen' => $row['first_seen'] ?? null,
            'last_seen' => $row['last_seen'] ?? null,
            'times_seen' => (int) ($row['times_seen'] ?? 1),
            'days_on_market' => (int) ($row['days_on_market'] ?? 1),
            'source' => (string) ($row['source'] ?? ''),
            'notes' => $row['notes'] ?? null,
            'description' => $row['description'] ?? null,
            'resume_file' => $row['resume_file'] ?? null,
            'cover_letter_file' => $row['cover_letter_file'] ?? null,
            'resume_lane' => (string) ($row['resume_lane'] ?? ''),
            'review_summary_file' => $row['review_summary_file'] ?? null,
            'generated_docs_summary' => $row['generated_docs_summary'] ?? null,
            'application_status' => (string) ($row['application_status'] ?? 'Not Started'),
            'application_missing_fields' => $row['application_missing_fields'] ?? null,
            'application_warnings' => $row['application_warnings'] ?? null,
            'application_screenshot' => $row['application_screenshot'] ?? null,
            'application_review_file' => $row['application_review_file'] ?? null,
            'application_last_action' => $row['application_last_action'] ?? null,
            'application_ready_at' => $row['application_ready_at'] ?? null,
            'approval_required' => (bool) ($row['approval_required'] ?? true),
            'created_at' => $row['created_at'] ?? now(),
            'updated_at' => $row['updated_at'] ?? now(),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function validateJobRow(array $row): void
    {
        $this->mapJobRow($row);
    }

    private function recordDocumentReferences(Job $job, bool $copyFiles): int
    {
        $count = 0;

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

            $storedPath = $copyFiles ? $this->copyDocumentReference($reference) : null;

            GeneratedDocument::query()->updateOrCreate([
                'job_id' => $job->id,
                'document_type' => $type,
                'v1_reference' => $reference,
            ], [
                'stored_path' => $storedPath,
                'mime_type' => $storedPath ? Storage::mimeType($storedPath) : null,
                'size_bytes' => $storedPath ? Storage::size($storedPath) : null,
            ]);
            $count++;
        }

        return $count;
    }

    private function copyDocumentReference(string $reference): ?string
    {
        $root = trim((string) config('jobsearch.v1_generated_files_path'));

        if ($root === '') {
            return null;
        }

        $normalized = str_replace('\\', '/', $reference);

        if (str_contains($normalized, "\0") || preg_match('#(^|/)\.\.(/|$)#', $normalized)) {
            return null;
        }

        $source = rtrim($root, '\\/').DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $normalized);

        if (! is_file($source)) {
            return null;
        }

        $target = 'generated-documents/v1-import/'.$normalized;
        Storage::put($target, file_get_contents($source) ?: '');

        return $target;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function recordRowError(DataImportRun $run, string $table, string $sourceId, string $message, array $payload): void
    {
        $run->rowErrors()->create([
            'source_table' => $table,
            'source_id' => $sourceId,
            'message' => Str::limit($message, 2000),
            'payload' => $payload,
        ]);
    }
}
