<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SourceConnection extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'display_name',
        'encrypted_credentials',
        'status',
        'last_sync_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'last_sync_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
