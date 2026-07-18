<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataImportRowError extends Model
{
    protected $fillable = [
        'data_import_run_id',
        'source_table',
        'source_id',
        'message',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public function importRun(): BelongsTo
    {
        return $this->belongsTo(DataImportRun::class, 'data_import_run_id');
    }
}
