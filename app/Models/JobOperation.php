<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobOperation extends Model
{
    public const STATUSES = [
        'queued',
        'processing',
        'completed',
        'failed',
    ];

    public const TYPES = [
        'resume_generation',
        'cover_letter_generation',
        'job_source_refresh',
        'bulk_scoring',
        'email_lead_import',
        'full_description_retrieval',
        'deduplication',
    ];

    protected $fillable = [
        'user_id',
        'job_id',
        'operation_type',
        'status',
        'metadata',
        'failure_reason',
        'queued_at',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'queued_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }
}
