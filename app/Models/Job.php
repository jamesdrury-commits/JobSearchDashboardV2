<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Job extends Model
{
    use HasFactory;

    public const WORKFLOW_STATUSES = [
        'Sourced - Needs Review',
        'Interested',
        'Apply Soon',
        'Generate Requested',
        'Pass',
        'Ready for Review',
        'Applied',
        'Submitted',
        'Docs Generated',
        'Browser Started',
        'Partially Filled',
        'Needs Manual Review',
        'Skipped',
        'Needs Follow-up',
    ];

    public const APPLICATION_STATUSES = [
        'Not Started',
        'Docs Generated',
        'Browser Started',
        'Partially Filled',
        'Ready for Review',
        'Needs Manual Review',
        'Submitted',
        'Skipped',
        'Needs Follow-up',
    ];

    protected $fillable = [
        'v1_job_id',
        'company',
        'role',
        'url',
        'url_hash',
        'salary',
        'remote_status',
        'match_score',
        'career_fit_score',
        'life_fit_score',
        'overall_recommendation',
        'why_considering',
        'tradeoffs_watch_outs',
        'local_exception_reason',
        'commute_notes',
        'benefits_pension_notes',
        'resume_angle',
        'source_lane',
        'executive_watch',
        'status',
        'first_seen',
        'last_seen',
        'times_seen',
        'days_on_market',
        'source',
        'notes',
        'description',
        'resume_file',
        'cover_letter_file',
        'resume_lane',
        'review_summary_file',
        'generated_docs_summary',
        'application_status',
        'application_missing_fields',
        'application_warnings',
        'application_screenshot',
        'application_review_file',
        'application_last_action',
        'application_ready_at',
        'approval_required',
    ];

    protected function casts(): array
    {
        return [
            'executive_watch' => 'boolean',
            'approval_required' => 'boolean',
            'first_seen' => 'date:Y-m-d',
            'last_seen' => 'date:Y-m-d',
            'application_ready_at' => 'datetime',
        ];
    }

    public function events(): HasMany
    {
        return $this->hasMany(JobEvent::class);
    }

    public function generatedDocuments(): HasMany
    {
        return $this->hasMany(GeneratedDocument::class);
    }

    public static function urlHash(?string $url, string $company = '', string $role = ''): string
    {
        $normalizedUrl = trim((string) $url);

        if ($normalizedUrl !== '') {
            return hash('sha256', rtrim(strtolower($normalizedUrl), '/'));
        }

        return hash('sha256', strtolower(trim($company)).'|'.strtolower(trim($role)));
    }
}
