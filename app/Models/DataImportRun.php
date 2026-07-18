<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DataImportRun extends Model
{
    protected $fillable = [
        'source_name',
        'mode',
        'status',
        'jobs_seen',
        'jobs_imported',
        'events_imported',
        'documents_imported',
        'errors_count',
        'metadata',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function rowErrors(): HasMany
    {
        return $this->hasMany(DataImportRowError::class);
    }
}
