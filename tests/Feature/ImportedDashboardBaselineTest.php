<?php

namespace Tests\Feature;

use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportedDashboardBaselineTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
    }

    public function test_imported_scores_match_v2_import_baseline(): void
    {
        $baseline = $this->baseline();

        foreach ($baseline['top_20'] as $row) {
            $this->createJobFromBaselineRow($row);
        }

        foreach ($baseline['top_20'] as $row) {
            $job = Job::query()
                ->where('v1_job_id', $row['v1_job_id'])
                ->firstOrFail();

            $this->assertSame($row['career_fit_score'], $job->career_fit_score, "Career Fit changed for V1 job {$row['v1_job_id']}.");
            $this->assertSame($row['life_fit_score'], $job->life_fit_score, "Life Fit changed for V1 job {$row['v1_job_id']}.");
            $this->assertSame($row['priority_score'], $job->match_score, "Priority Score changed for V1 job {$row['v1_job_id']}.");
        }
    }

    public function test_dashboard_top_twenty_order_matches_v2_import_baseline(): void
    {
        $baseline = $this->baseline();

        foreach ($baseline['top_20'] as $row) {
            $this->createJobFromBaselineRow($row);
        }

        Job::query()->create([
            'v1_job_id' => 900000001,
            'user_id' => $this->owner->id,
            'company' => 'Lower Ranked Baseline Control',
            'role' => 'Control Role',
            'url_hash' => Job::urlHash('', 'Lower Ranked Baseline Control', 'Control Role'),
            'career_fit_score' => 99,
            'life_fit_score' => 100,
            'match_score' => 100,
            'overall_recommendation' => 'Apply',
            'last_seen' => '2026-07-18',
        ]);

        $expected = array_column($baseline['top_20'], 'v1_job_id');
        $actual = Job::query()
            ->dashboardRanked()
            ->limit(20)
            ->pluck('v1_job_id')
            ->all();

        $this->assertSame($expected, $actual);
    }

    /**
     * @return array<string, mixed>
     */
    private function baseline(): array
    {
        return json_decode(
            file_get_contents(base_path('tests/Fixtures/v2-import-baseline.json')) ?: '',
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function createJobFromBaselineRow(array $row): Job
    {
        return Job::query()->create([
            'v1_job_id' => $row['v1_job_id'],
            'user_id' => $this->owner->id,
            'company' => $row['company'],
            'role' => $row['role'],
            'url_hash' => Job::urlHash('', $row['company'], $row['role']),
            'career_fit_score' => $row['career_fit_score'],
            'life_fit_score' => $row['life_fit_score'],
            'match_score' => $row['priority_score'],
            'overall_recommendation' => $row['overall_recommendation'],
            'status' => $row['status'],
            'source' => $row['source'],
            'last_seen' => $row['last_seen'],
        ]);
    }
}
