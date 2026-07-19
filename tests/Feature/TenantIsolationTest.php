<?php

namespace Tests\Feature;

use App\Models\GeneratedDocument;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;

    private User $userB;

    private Job $userAJob;

    private Job $userBJob;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = User::factory()->create();
        $this->userB = User::factory()->create();
        $this->userAJob = $this->createJob($this->userA, 'Owner A Company');
        $this->userBJob = $this->createJob($this->userB, 'Owner B Company');
    }

    public function test_user_b_cannot_list_or_search_user_a_jobs(): void
    {
        $this->actingAs($this->userB);

        $this->getJson('/api.php?action=list&search=Owner A Company')
            ->assertOk()
            ->assertJsonMissing(['company' => 'Owner A Company'])
            ->assertJsonFragment(['company' => 'Owner B Company']);
    }

    public function test_user_b_dashboard_does_not_include_user_a_jobs(): void
    {
        $this->actingAs($this->userB);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->has('jobs.data', 1)
                ->where('jobs.data.0.company', 'Owner B Company'));
    }

    public function test_user_b_cannot_view_user_a_job_details(): void
    {
        $this->actingAs($this->userB);

        $this->getJson(route('dashboard.jobs.show', $this->userAJob))
            ->assertNotFound();
    }

    public function test_user_b_cannot_view_or_delete_user_a_job_by_policy(): void
    {
        $this->assertFalse(Gate::forUser($this->userB)->allows('view', $this->userAJob));
        $this->assertFalse(Gate::forUser($this->userB)->allows('delete', $this->userAJob));
    }

    public function test_user_b_cannot_modify_user_a_job_status(): void
    {
        $this->actingAs($this->userB);

        $this->postJson('/api.php?action=status', [
            'id' => $this->userAJob->id,
            'status' => 'Apply Soon',
        ])->assertNotFound();

        $this->assertSame('Sourced - Needs Review', $this->userAJob->refresh()->status);
    }

    public function test_user_b_cannot_generate_documents_for_user_a_job(): void
    {
        $this->actingAs($this->userB);

        $this->postJson('/api.php?action=generate', [
            'id' => $this->userAJob->id,
        ])->assertNotFound();

        $this->assertSame('Sourced - Needs Review', $this->userAJob->refresh()->status);
    }

    public function test_user_b_cannot_search_user_a_job_from_dashboard(): void
    {
        $this->actingAs($this->userB);

        $this->get(route('dashboard', ['q' => 'Owner A Company']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->has('jobs.data', 0));
    }

    public function test_user_b_cannot_download_user_a_documents(): void
    {
        GeneratedDocument::query()->create([
            'user_id' => $this->userA->id,
            'job_id' => $this->userAJob->id,
            'document_type' => 'resume',
            'v1_reference' => 'owner-a-resume.pdf',
        ]);

        $this->actingAs($this->userB);

        $this->getJson('/api.php?action=file&path=owner-a-resume.pdf')
            ->assertNotFound();
    }

    private function createJob(User $user, string $company): Job
    {
        return Job::query()->create([
            'user_id' => $user->id,
            'company' => $company,
            'role' => 'CRM Administrator',
            'url_hash' => Job::urlHash('', $company, 'CRM Administrator'),
            'match_score' => 80,
            'career_fit_score' => 80,
            'life_fit_score' => 75,
            'overall_recommendation' => 'Apply',
            'status' => 'Sourced - Needs Review',
            'last_seen' => '2026-07-19',
        ]);
    }
}
