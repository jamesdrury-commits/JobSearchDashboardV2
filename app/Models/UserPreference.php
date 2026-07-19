<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    protected $fillable = [
        'user_id',
        'career_profile_markdown',
        'resume_background_markdown',
        'preferred_roles',
        'desired_salary_min',
        'desired_salary_target',
        'remote_preference',
        'commute_distance_miles',
        'travel_tolerance',
        'benefit_requirements_markdown',
        'after_hours_tolerance',
        'scoring_weights',
        'preferred_industries',
        'excluded_industries',
    ];

    protected function casts(): array
    {
        return [
            'preferred_roles' => 'array',
            'scoring_weights' => 'array',
            'preferred_industries' => 'array',
            'excluded_industries' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
