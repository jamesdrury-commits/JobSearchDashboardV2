<?php

namespace App\Http\Controllers;

use App\Models\DashboardRunStatus;
use App\Models\Job;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $jobs = Job::query()
            ->with('generatedDocuments')
            ->dashboardRanked()
            ->get();

        return Inertia::render('dashboard', [
            'jobs' => $jobs,
            'workflowStatuses' => Job::WORKFLOW_STATUSES,
            'applicationStatuses' => Job::APPLICATION_STATUSES,
            'runStatuses' => DashboardRunStatus::query()->get()->keyBy('run_name'),
            'metrics' => [
                'all' => $jobs->count(),
                'high' => $jobs->filter(fn (Job $job): bool => $job->career_fit_score >= 70 || strtolower($job->overall_recommendation) === 'apply')->count(),
                'medium' => $jobs->filter(fn (Job $job): bool => $job->career_fit_score >= 25 && $job->career_fit_score < 70 && strtolower($job->overall_recommendation) !== 'pass')->count(),
                'low' => $jobs->filter(fn (Job $job): bool => $job->career_fit_score < 25 || strtolower($job->overall_recommendation) === 'pass')->count(),
                'applied' => $jobs->filter(fn (Job $job): bool => in_array($job->status, ['Applied', 'Submitted'], true) || $job->application_status === 'Submitted')->count(),
            ],
        ]);
    }
}
