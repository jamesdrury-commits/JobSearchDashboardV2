<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('v1_job_id')->nullable()->unique();
            $table->string('company')->default('');
            $table->string('role')->default('');
            $table->text('url')->nullable();
            $table->char('url_hash', 64)->unique();
            $table->string('salary', 100)->default('');
            $table->string('remote_status', 100)->default('');
            $table->integer('match_score')->default(0);
            $table->integer('career_fit_score')->default(0);
            $table->integer('life_fit_score')->default(0);
            $table->string('overall_recommendation', 20)->default('');
            $table->text('why_considering')->nullable();
            $table->text('tradeoffs_watch_outs')->nullable();
            $table->text('local_exception_reason')->nullable();
            $table->text('commute_notes')->nullable();
            $table->text('benefits_pension_notes')->nullable();
            $table->text('resume_angle')->nullable();
            $table->string('source_lane', 60)->default('');
            $table->boolean('executive_watch')->default(false);
            $table->string('status', 50)->default('Sourced - Needs Review')->index();
            $table->date('first_seen')->nullable();
            $table->date('last_seen')->nullable()->index();
            $table->integer('times_seen')->default(1);
            $table->integer('days_on_market')->default(1);
            $table->string('source', 100)->default('');
            $table->text('notes')->nullable();
            $table->mediumText('description')->nullable();
            $table->text('resume_file')->nullable();
            $table->text('cover_letter_file')->nullable();
            $table->string('resume_lane', 100)->default('');
            $table->text('review_summary_file')->nullable();
            $table->text('generated_docs_summary')->nullable();
            $table->string('application_status', 50)->default('Not Started');
            $table->text('application_missing_fields')->nullable();
            $table->text('application_warnings')->nullable();
            $table->text('application_screenshot')->nullable();
            $table->text('application_review_file')->nullable();
            $table->text('application_last_action')->nullable();
            $table->dateTime('application_ready_at')->nullable();
            $table->boolean('approval_required')->default(true);
            $table->timestamps();

            $table->index('career_fit_score');
            $table->index('life_fit_score');
            $table->index(['overall_recommendation', 'career_fit_score', 'life_fit_score'], 'jobs_recommendation_scores_index');
        });

        Schema::create('job_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('v1_event_id')->nullable()->unique();
            $table->string('event_type', 50);
            $table->text('event_note')->nullable();
            $table->timestamps();
        });

        Schema::create('dashboard_run_status', function (Blueprint $table) {
            $table->string('run_name', 50)->primary();
            $table->string('last_run_at', 40)->default('');
            $table->string('status', 40)->default('');
            $table->text('details')->nullable();
            $table->timestamps();
        });

        Schema::create('generated_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 50);
            $table->text('v1_reference')->nullable();
            $table->text('stored_path')->nullable();
            $table->string('mime_type', 160)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->timestamps();

            $table->index(['job_id', 'document_type']);
        });

        Schema::create('data_import_runs', function (Blueprint $table) {
            $table->id();
            $table->string('source_name', 80)->default('v1_job_search_assistant');
            $table->string('mode', 30)->default('dry-run');
            $table->string('status', 30)->default('started');
            $table->unsignedInteger('jobs_seen')->default(0);
            $table->unsignedInteger('jobs_imported')->default(0);
            $table->unsignedInteger('events_imported')->default(0);
            $table->unsignedInteger('documents_imported')->default(0);
            $table->unsignedInteger('errors_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('data_import_row_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_import_run_id')->constrained()->cascadeOnDelete();
            $table->string('source_table', 80);
            $table->string('source_id', 80)->nullable();
            $table->text('message');
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_import_row_errors');
        Schema::dropIfExists('data_import_runs');
        Schema::dropIfExists('generated_documents');
        Schema::dropIfExists('dashboard_run_status');
        Schema::dropIfExists('job_events');
        Schema::dropIfExists('jobs');
    }
};
