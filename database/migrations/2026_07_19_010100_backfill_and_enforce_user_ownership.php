<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $ownerId = DB::table('users')->orderBy('id')->value('id');
        $hasImportedJobs = DB::table('jobs')->exists();

        if ($hasImportedJobs && ! $ownerId) {
            throw new RuntimeException('Cannot backfill imported jobs without an owner user.');
        }

        if ($ownerId) {
            DB::table('jobs')
                ->whereNull('user_id')
                ->update(['user_id' => $ownerId]);

            DB::table('user_preferences')->insertOrIgnore([
                'user_id' => $ownerId,
                'career_profile_markdown' => '',
                'resume_background_markdown' => '',
                'preferred_roles' => json_encode([]),
                'remote_preference' => 'remote_or_hybrid',
                'travel_tolerance' => 'low',
                'after_hours_tolerance' => 'low',
                'scoring_weights' => json_encode([
                    'career_fit' => 0.5,
                    'life_fit' => 0.3,
                    'priority' => 0.2,
                ]),
                'preferred_industries' => json_encode([]),
                'excluded_industries' => json_encode([]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->backfillJobOwnedTables();
            $this->backfillApplications();
            $this->backfillJobNotes();
            $this->backfillDocuments();
            $this->backfillSourceConnections($ownerId);
        }

        Schema::table('jobs', function (Blueprint $table): void {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['user_id', 'overall_recommendation', 'career_fit_score', 'life_fit_score'], 'jobs_user_dashboard_rank_index');
            $table->index(['user_id', 'status'], 'jobs_user_status_index');
        });

        Schema::table('job_events', function (Blueprint $table): void {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['user_id', 'job_id'], 'job_events_user_job_index');
        });

        Schema::table('generated_documents', function (Blueprint $table): void {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['user_id', 'job_id'], 'generated_documents_user_job_index');
        });
    }

    public function down(): void
    {
        Schema::table('generated_documents', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->dropIndex('generated_documents_user_job_index');
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });

        Schema::table('job_events', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->dropIndex('job_events_user_job_index');
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });

        Schema::table('jobs', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->dropIndex('jobs_user_dashboard_rank_index');
            $table->dropIndex('jobs_user_status_index');
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });
    }

    private function backfillJobOwnedTables(): void
    {
        DB::table('job_events')
            ->whereNull('user_id')
            ->orderBy('id')
            ->select(['id', 'job_id'])
            ->chunkById(200, function ($events): void {
                $userIdsByJobId = DB::table('jobs')
                    ->whereIn('id', $events->pluck('job_id')->all())
                    ->pluck('user_id', 'id');

                foreach ($events as $event) {
                    DB::table('job_events')
                        ->where('id', $event->id)
                        ->update(['user_id' => $userIdsByJobId[$event->job_id]]);
                }
            });

        DB::table('generated_documents')
            ->whereNull('user_id')
            ->orderBy('id')
            ->select(['id', 'job_id'])
            ->chunkById(200, function ($documents): void {
                $userIdsByJobId = DB::table('jobs')
                    ->whereIn('id', $documents->pluck('job_id')->all())
                    ->pluck('user_id', 'id');

                foreach ($documents as $document) {
                    DB::table('generated_documents')
                        ->where('id', $document->id)
                        ->update(['user_id' => $userIdsByJobId[$document->job_id]]);
                }
            });
    }

    private function backfillApplications(): void
    {
        DB::table('jobs')
            ->orderBy('id')
            ->select(['id', 'user_id', 'application_status', 'application_last_action', 'application_ready_at', 'application_missing_fields', 'application_warnings', 'created_at', 'updated_at'])
            ->chunkById(200, function ($jobs): void {
                $rows = $jobs->map(fn ($job): array => [
                    'user_id' => $job->user_id,
                    'job_id' => $job->id,
                    'status' => $job->application_status ?: 'Not Started',
                    'last_action_at' => $job->application_ready_at,
                    'last_action' => $job->application_last_action,
                    'missing_fields' => $job->application_missing_fields,
                    'warnings' => $job->application_warnings,
                    'created_at' => $job->created_at ?? now(),
                    'updated_at' => $job->updated_at ?? now(),
                ])->all();

                DB::table('applications')->insertOrIgnore($rows);
            });
    }

    private function backfillJobNotes(): void
    {
        DB::table('jobs')
            ->whereNotNull('notes')
            ->where('notes', '<>', '')
            ->orderBy('id')
            ->select(['id', 'user_id', 'notes', 'created_at', 'updated_at'])
            ->chunkById(200, function ($jobs): void {
                $rows = $jobs->map(fn ($job): array => [
                    'user_id' => $job->user_id,
                    'job_id' => $job->id,
                    'body_markdown' => $job->notes,
                    'source' => 'v1_import',
                    'created_at' => $job->created_at ?? now(),
                    'updated_at' => $job->updated_at ?? now(),
                ])->all();

                DB::table('job_notes')->insert($rows);
            });
    }

    private function backfillDocuments(): void
    {
        DB::table('generated_documents')
            ->join('jobs', 'generated_documents.job_id', '=', 'jobs.id')
            ->orderBy('generated_documents.id')
            ->select([
                'generated_documents.id',
                'generated_documents.user_id',
                'generated_documents.job_id',
                'generated_documents.document_type',
                'generated_documents.v1_reference',
                'generated_documents.stored_path',
                'generated_documents.mime_type',
                'generated_documents.size_bytes',
                'generated_documents.created_at',
                'generated_documents.updated_at',
                'jobs.company',
                'jobs.role',
            ])
            ->chunkById(200, function ($documents): void {
                $rows = $documents->map(function ($document): array {
                    $reference = str_replace('\\', '/', (string) ($document->v1_reference ?: $document->stored_path));
                    $originalFilename = basename($reference);
                    $type = str_replace('_', ' ', (string) $document->document_type);

                    return [
                        'user_id' => $document->user_id,
                        'job_id' => $document->job_id,
                        'generated_document_id' => $document->id,
                        'document_type' => $document->document_type,
                        'disk' => 'local',
                        'path' => $document->stored_path,
                        'display_filename' => Str::limit(trim($document->company.' - '.$type), 255, ''),
                        'original_filename' => $originalFilename !== '' ? Str::limit($originalFilename, 255, '') : null,
                        'mime_type' => $document->mime_type,
                        'size_bytes' => $document->size_bytes,
                        'created_at' => $document->created_at ?? now(),
                        'updated_at' => $document->updated_at ?? now(),
                    ];
                })->all();

                DB::table('documents')->insert($rows);
            }, 'generated_documents.id', 'id');
    }

    private function backfillSourceConnections(int $ownerId): void
    {
        foreach (DB::table('jobs')->where('source', '<>', '')->distinct()->pluck('source') as $source) {
            DB::table('source_connections')->insertOrIgnore([
                'user_id' => $ownerId,
                'provider' => Str::slug((string) $source, '_') ?: 'unknown',
                'display_name' => (string) $source,
                'status' => 'imported',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
