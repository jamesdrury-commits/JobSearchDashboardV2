<?php

namespace App\Jobs;

use App\Models\JobOperation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunDashboardOperation implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public readonly int $operationId) {}

    public function handle(): void
    {
        $operation = JobOperation::query()->findOrFail($this->operationId);

        $operation->update([
            'status' => 'processing',
            'started_at' => now(),
            'failure_reason' => null,
        ]);

        $metadata = $operation->metadata ?? [];
        $metadata['placeholder'] = true;
        $metadata['message'] = $this->completionMessage($operation->operation_type);

        $operation->update([
            'status' => 'completed',
            'metadata' => $metadata,
            'finished_at' => now(),
        ]);

        if ($operation->job) {
            $operation->job->events()->create([
                'user_id' => $operation->user_id,
                'event_type' => 'operation_completed',
                'event_note' => $metadata['message'],
            ]);
        }
    }

    public function failed(?\Throwable $exception): void
    {
        JobOperation::query()
            ->whereKey($this->operationId)
            ->update([
                'status' => 'failed',
                'failure_reason' => $exception?->getMessage() ?? 'Operation failed.',
                'finished_at' => now(),
            ]);
    }

    private function completionMessage(string $operationType): string
    {
        return match ($operationType) {
            'resume_generation' => 'Resume generation queued operation completed.',
            'cover_letter_generation' => 'Cover-letter generation queued operation completed.',
            'job_source_refresh' => 'Job source refresh queued operation completed.',
            'bulk_scoring' => 'Bulk scoring queued operation completed.',
            'email_lead_import' => 'Email lead import queued operation completed.',
            'full_description_retrieval' => 'Full-description retrieval queued operation completed.',
            'deduplication' => 'Deduplication queued operation completed.',
            default => 'Queued operation completed.',
        };
    }
}
