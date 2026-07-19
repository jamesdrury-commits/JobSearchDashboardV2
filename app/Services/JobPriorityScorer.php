<?php

namespace App\Services;

use App\Models\Job;
use App\Models\UserPreference;

class JobPriorityScorer
{
    /**
     * Calculate the user-facing Priority Score from the authenticated user's
     * scoring preferences while preserving V1's match score as the fallback.
     */
    public function score(Job $job, ?UserPreference $preferences): int
    {
        $weights = $this->weights($preferences);
        $total = array_sum($weights);

        if ($total <= 0.0) {
            return (int) $job->match_score;
        }

        $score = (
            ((float) $job->match_score * $weights['priority']) +
            ((float) $job->career_fit_score * $weights['career_fit']) +
            ((float) $job->life_fit_score * $weights['life_fit'])
        ) / $total;

        return max(0, min(100, (int) round($score)));
    }

    /**
     * @return array{priority: float, career_fit: float, life_fit: float}
     */
    public function weights(?UserPreference $preferences): array
    {
        $stored = $preferences?->scoring_weights ?? [];

        return [
            'priority' => (float) ($stored['priority'] ?? $stored['match'] ?? 1.0),
            'career_fit' => (float) ($stored['career_fit'] ?? $stored['career'] ?? 0.0),
            'life_fit' => (float) ($stored['life_fit'] ?? $stored['life'] ?? 0.0),
        ];
    }

    public function explanation(Job $job, ?UserPreference $preferences): string
    {
        $weights = $this->weights($preferences);

        return sprintf(
            'Priority uses this account scoring profile: V1 priority %s, Career Fit %s, Life Fit %s. Imported scores are Priority %d, Career Fit %d, Life Fit %d.',
            $this->formatWeight($weights['priority']),
            $this->formatWeight($weights['career_fit']),
            $this->formatWeight($weights['life_fit']),
            $job->match_score,
            $job->career_fit_score,
            $job->life_fit_score,
        );
    }

    private function formatWeight(float $weight): string
    {
        return rtrim(rtrim(number_format($weight, 2), '0'), '.');
    }
}
