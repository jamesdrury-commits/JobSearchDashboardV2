<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Application extends Model
{
    protected $fillable = [
        'user_id',
        'job_id',
        'status',
        'applied_at',
        'last_action_at',
        'last_action',
        'missing_fields',
        'warnings',
    ];

    protected function casts(): array
    {
        return [
            'applied_at' => 'datetime',
            'last_action_at' => 'datetime',
            'missing_fields' => 'array',
            'warnings' => 'array',
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
