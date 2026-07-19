<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->after('id')->index();
        });

        Schema::table('job_events', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->after('id')->index();
        });

        Schema::table('generated_documents', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->after('id')->index();
        });

        Schema::create('user_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->longText('career_profile_markdown')->nullable();
            $table->longText('resume_background_markdown')->nullable();
            $table->json('preferred_roles')->nullable();
            $table->unsignedInteger('desired_salary_min')->nullable();
            $table->unsignedInteger('desired_salary_target')->nullable();
            $table->string('remote_preference', 40)->default('remote_or_hybrid');
            $table->unsignedSmallInteger('commute_distance_miles')->nullable();
            $table->string('travel_tolerance', 40)->default('low');
            $table->longText('benefit_requirements_markdown')->nullable();
            $table->string('after_hours_tolerance', 40)->default('low');
            $table->json('scoring_weights')->nullable();
            $table->json('preferred_industries')->nullable();
            $table->json('excluded_industries')->nullable();
            $table->timestamps();
        });

        Schema::create('applications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_id')->constrained()->cascadeOnDelete();
            $table->string('status', 50)->default('Not Started');
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('last_action_at')->nullable();
            $table->text('last_action')->nullable();
            $table->json('missing_fields')->nullable();
            $table->json('warnings')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'job_id']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('generated_document_id')->nullable()->constrained('generated_documents')->nullOnDelete();
            $table->string('document_type', 50);
            $table->string('disk', 40)->default('local');
            $table->text('path')->nullable();
            $table->string('display_filename');
            $table->string('original_filename')->nullable();
            $table->string('mime_type', 160)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->char('checksum_sha256', 64)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'document_type']);
            $table->index(['job_id', 'document_type']);
        });

        Schema::create('job_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_id')->constrained()->cascadeOnDelete();
            $table->longText('body_markdown');
            $table->string('source', 40)->default('manual');
            $table->timestamps();

            $table->index(['user_id', 'job_id']);
        });

        Schema::create('source_connections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 80);
            $table->string('display_name')->default('');
            $table->text('encrypted_credentials')->nullable();
            $table->string('status', 40)->default('inactive');
            $table->timestamp('last_sync_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'provider', 'display_name'], 'source_connections_user_provider_name_unique');
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('source_connections');
        Schema::dropIfExists('job_notes');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('applications');
        Schema::dropIfExists('user_preferences');

        Schema::table('generated_documents', function (Blueprint $table): void {
            $table->dropColumn('user_id');
        });

        Schema::table('job_events', function (Blueprint $table): void {
            $table->dropColumn('user_id');
        });

        Schema::table('jobs', function (Blueprint $table): void {
            $table->dropColumn('user_id');
        });
    }
};
