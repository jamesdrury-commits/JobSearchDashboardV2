<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobEvent extends Model
{
    protected $fillable = [
        'job_id',
        'v1_event_id',
        'event_type',
        'event_note',
        'created_at',
        'updated_at',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }
}
