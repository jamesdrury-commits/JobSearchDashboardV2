<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table): void {
            $table->dropUnique('jobs_url_hash_unique');
            $table->unique(['user_id', 'url_hash'], 'jobs_user_url_hash_unique');
        });
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table): void {
            $table->dropUnique('jobs_user_url_hash_unique');
            $table->unique('url_hash', 'jobs_url_hash_unique');
        });
    }
};
