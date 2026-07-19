<?php

namespace Tests\Feature;

use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_dashboard_list_uses_summary_payload_without_full_descriptions(): void
    {
        $user = User::factory()->create();
        Job::query()->create([
            'user_id' => $user->id,
            'company' => 'Summary Payload Co',
            'role' => 'CRM Administrator',
            'url' => 'https://example.com/jobs/summary-payload',
            'url_hash' => Job::urlHash('https://example.com/jobs/summary-payload'),
            'match_score' => 91,
            'career_fit_score' => 88,
            'life_fit_score' => 82,
            'overall_recommendation' => 'Apply',
            'description' => 'This full description should only load in the drawer.',
        ]);

        $this->actingAs($user);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->has('topJobs', 1)
                ->where('topJobs.0.url', 'https://example.com/jobs/summary-payload')
                ->has('jobs.data', 1)
                ->where('jobs.data.0.company', 'Summary Payload Co')
                ->missing('jobs.data.0.description'));
    }

    public function test_owner_can_load_full_job_details_on_demand(): void
    {
        $user = User::factory()->create();
        $job = Job::query()->create([
            'user_id' => $user->id,
            'company' => 'Details Drawer Co',
            'role' => 'Revenue Operations Manager',
            'url' => 'https://example.com/jobs/details-drawer',
            'url_hash' => Job::urlHash('https://example.com/jobs/details-drawer'),
            'match_score' => 86,
            'career_fit_score' => 84,
            'life_fit_score' => 79,
            'overall_recommendation' => 'Apply',
            'description' => 'Full description belongs in the drawer response.',
        ]);

        $this->actingAs($user);

        $this->getJson(route('dashboard.jobs.show', $job))
            ->assertOk()
            ->assertJsonPath('company', 'Details Drawer Co')
            ->assertJsonPath('description', 'Full description belongs in the drawer response.')
            ->assertJsonStructure(['priority_score', 'score_explanation']);
    }
}
