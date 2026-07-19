<?php

namespace App\Http\Controllers;

use App\Models\DashboardRunStatus;
use App\Models\Job;
use App\Models\UserPreference;
use App\Services\JobPriorityScorer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, JobPriorityScorer $scorer): Response
    {
        $preferences = $request->user()->preferences()->first();
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => trim((string) $request->query('status', 'all')),
            'bucket' => trim((string) $request->query('bucket', 'all')),
            'view' => trim((string) $request->query('view', 'compact')),
        ];
        $perPage = min(max((int) $request->query('per_page', 25), 10), 50);

        $userJobs = fn (): Builder => $request->user()->jobs()->getQuery();

        $jobs = $this->applyFilters($userJobs(), $filters)
            ->select($this->summaryColumns())
            ->with(['latestOperation' => fn ($query) => $query->select($this->latestOperationColumns())])
            ->withCount(['documents', 'generatedDocuments'])
            ->dashboardRanked()
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Job $job): array => $this->summaryPayload($job, $scorer, $preferences));

        $topJobs = $userJobs()
            ->select($this->summaryColumns())
            ->with(['latestOperation' => fn ($query) => $query->select($this->latestOperationColumns())])
            ->withCount(['documents', 'generatedDocuments'])
            ->dashboardRanked()
            ->limit(20)
            ->get()
            ->map(fn (Job $job): array => $this->summaryPayload($job, $scorer, $preferences));

        return Inertia::render('dashboard', [
            'jobs' => $jobs,
            'topJobs' => $topJobs,
            'filters' => $filters,
            'workflowStatuses' => Job::WORKFLOW_STATUSES,
            'applicationStatuses' => Job::APPLICATION_STATUSES,
            'runStatuses' => DashboardRunStatus::query()->get()->keyBy('run_name'),
            'metrics' => [
                'all' => $userJobs()->count(),
                'high' => $userJobs()
                    ->where(fn (Builder $query): Builder => $query
                        ->where('career_fit_score', '>=', 70)
                        ->orWhere('overall_recommendation', 'Apply'))
                    ->count(),
                'medium' => $userJobs()
                    ->where('career_fit_score', '>=', 25)
                    ->where('career_fit_score', '<', 70)
                    ->where('overall_recommendation', '!=', 'Pass')
                    ->count(),
                'low' => $userJobs()
                    ->where(fn (Builder $query): Builder => $query
                        ->where('career_fit_score', '<', 25)
                        ->orWhere('overall_recommendation', 'Pass'))
                    ->count(),
                'applied' => $userJobs()
                    ->where(fn (Builder $query): Builder => $query
                        ->whereIn('status', ['Applied', 'Submitted'])
                        ->orWhere('application_status', 'Submitted'))
                    ->count(),
            ],
        ]);
    }

    /**
     * @param  Builder<Job>  $query
     * @param  array{q: string, status: string, bucket: string, view: string}  $filters
     * @return Builder<Job>
     */
    private function applyFilters(Builder $query, array $filters): Builder
    {
        if ($filters['q'] !== '') {
            $q = '%'.$filters['q'].'%';
            $query->where(fn (Builder $query): Builder => $query
                ->where('company', 'like', $q)
                ->orWhere('role', 'like', $q)
                ->orWhere('salary', 'like', $q)
                ->orWhere('remote_status', 'like', $q)
                ->orWhere('source', 'like', $q)
                ->orWhere('source_lane', 'like', $q)
                ->orWhere('notes', 'like', $q)
                ->orWhere('why_considering', 'like', $q)
                ->orWhere('tradeoffs_watch_outs', 'like', $q)
                ->orWhere('resume_angle', 'like', $q)
                ->orWhere('application_status', 'like', $q));
        }

        if ($filters['status'] !== 'all' && in_array($filters['status'], Job::WORKFLOW_STATUSES, true)) {
            $query->where('status', $filters['status']);
        }

        match ($filters['bucket']) {
            'queue' => $query->whereIn('status', ['Apply Soon', 'Interested', 'Generate Requested', 'Ready for Review']),
            'interested' => $query->whereIn('status', ['Interested', 'Apply Soon']),
            'applied' => $query->where(fn (Builder $query): Builder => $query
                ->whereIn('status', ['Applied', 'Submitted'])
                ->orWhere('application_status', 'Submitted')),
            'review' => $query->where(fn (Builder $query): Builder => $query
                ->whereIn('status', ['Ready for Review', 'Needs Manual Review', 'Needs Follow-up'])
                ->orWhereIn('application_status', ['Ready for Review', 'Needs Manual Review', 'Needs Follow-up'])),
            default => null,
        };

        return $query;
    }

    /**
     * @return list<string>
     */
    private function summaryColumns(): array
    {
        return [
            'id',
            'user_id',
            'company',
            'role',
            'url',
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
            'last_seen',
            'source',
            'notes',
            'generated_docs_summary',
            'application_status',
            'application_ready_at',
            'approval_required',
        ];
    }

    /**
     * @return list<string>
     */
    private function latestOperationColumns(): array
    {
        return [
            'job_operations.id',
            'job_operations.user_id',
            'job_operations.job_id',
            'job_operations.operation_type',
            'job_operations.status',
            'job_operations.queued_at',
            'job_operations.started_at',
            'job_operations.finished_at',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function summaryPayload(Job $job, JobPriorityScorer $scorer, ?UserPreference $preferences): array
    {
        return [
            'id' => $job->id,
            'company' => $job->company,
            'role' => $job->role,
            'url' => $job->url,
            'salary' => $job->salary,
            'remote_status' => $job->remote_status,
            'match_score' => $job->match_score,
            'priority_score' => $scorer->score($job, $preferences),
            'career_fit_score' => $job->career_fit_score,
            'life_fit_score' => $job->life_fit_score,
            'overall_recommendation' => $job->overall_recommendation,
            'why_considering' => $job->why_considering,
            'tradeoffs_watch_outs' => $job->tradeoffs_watch_outs,
            'local_exception_reason' => $job->local_exception_reason,
            'commute_notes' => $job->commute_notes,
            'benefits_pension_notes' => $job->benefits_pension_notes,
            'resume_angle' => $job->resume_angle,
            'source_lane' => $job->source_lane,
            'executive_watch' => $job->executive_watch,
            'status' => $job->status,
            'last_seen' => $job->last_seen?->toDateString(),
            'source' => $job->source,
            'notes' => $job->notes,
            'generated_docs_summary' => $job->generated_docs_summary,
            'application_status' => $job->application_status,
            'application_ready_at' => $job->application_ready_at?->toIso8601String(),
            'approval_required' => $job->approval_required,
            'document_count' => ($job->documents_count ?? 0) + ($job->generated_documents_count ?? 0),
            'latest_operation' => $job->latestOperation ? [
                'id' => $job->latestOperation->id,
                'operation_type' => $job->latestOperation->operation_type,
                'status' => $job->latestOperation->status,
                'queued_at' => $job->latestOperation->queued_at?->toIso8601String(),
                'started_at' => $job->latestOperation->started_at?->toIso8601String(),
                'finished_at' => $job->latestOperation->finished_at?->toIso8601String(),
            ] : null,
        ];
    }
}
