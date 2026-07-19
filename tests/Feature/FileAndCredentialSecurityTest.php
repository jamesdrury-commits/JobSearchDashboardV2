<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\GeneratedDocument;
use App\Models\Job;
use App\Models\SourceConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileAndCredentialSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_download_document_through_authorized_route(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $job = $this->createJob($user);
        Storage::disk('local')->put('users/'.$user->id.'/jobs/'.$job->id.'/resume.pdf', 'resume');

        $document = Document::query()->create([
            'user_id' => $user->id,
            'job_id' => $job->id,
            'document_type' => 'resume',
            'disk' => 'local',
            'path' => 'users/'.$user->id.'/jobs/'.$job->id.'/resume.pdf',
            'display_filename' => 'friendly-resume.pdf',
        ]);

        $this->actingAs($user);

        $this->get(route('dashboard.documents.download', $document))
            ->assertOk()
            ->assertDownload('friendly-resume.pdf');
    }

    public function test_owner_can_download_generated_document_through_authorized_route(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $job = $this->createJob($user);
        Storage::disk('local')->put('generated-documents/resume.pdf', 'resume');

        $document = GeneratedDocument::query()->create([
            'user_id' => $user->id,
            'job_id' => $job->id,
            'document_type' => 'resume',
            'v1_reference' => 'resume.pdf',
            'stored_path' => 'generated-documents/resume.pdf',
        ]);

        $this->actingAs($user);

        $this->get(route('dashboard.generated-documents.download', $document))
            ->assertOk()
            ->assertDownload('resume.pdf');
    }

    public function test_source_connection_credentials_are_encrypted_at_rest(): void
    {
        $user = User::factory()->create();

        $connection = SourceConnection::query()->create([
            'user_id' => $user->id,
            'provider' => 'linkedin',
            'display_name' => 'LinkedIn',
            'encrypted_credentials' => ['token' => 'secret-token'],
            'status' => 'active',
        ]);

        $stored = DB::table('source_connections')
            ->where('id', $connection->id)
            ->value('encrypted_credentials');

        $this->assertIsString($stored);
        $this->assertStringNotContainsString('secret-token', $stored);
        $this->assertSame('secret-token', $connection->refresh()->encrypted_credentials['token']);
    }

    private function createJob(User $user): Job
    {
        return Job::query()->create([
            'user_id' => $user->id,
            'company' => 'Secure File Co',
            'role' => 'CRM Administrator',
            'url_hash' => Job::urlHash('', 'Secure File Co', 'CRM Administrator'),
            'match_score' => 80,
            'career_fit_score' => 80,
            'life_fit_score' => 75,
            'overall_recommendation' => 'Apply',
        ]);
    }
}
