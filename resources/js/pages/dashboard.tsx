import { Head, Link, router } from '@inertiajs/react';
import {
    BriefcaseBusiness,
    CheckCircle2,
    Gauge,
    RefreshCw,
    Search,
    Sparkles,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { FilterToolbar } from '@/components/dashboard/FilterToolbar';
import { FloatingBackToTopButton } from '@/components/dashboard/FloatingBackToTopButton';
import { JobCard } from '@/components/dashboard/JobCard';
import { JobDetailsDrawer } from '@/components/dashboard/JobDetailsDrawer';
import { TopJobsPanel } from '@/components/dashboard/TopJobsPanel';
import { Button } from '@/components/ui/button';
import type {
    DashboardFilters,
    JobSummary,
    PaginatedJobs,
    RunStatus,
} from '@/types/dashboard';

type DashboardProps = {
    jobs: PaginatedJobs;
    topJobs: JobSummary[];
    filters: DashboardFilters;
    workflowStatuses: string[];
    applicationStatuses: string[];
    runStatuses: Record<string, RunStatus>;
    metrics: {
        all: number;
        high: number;
        medium: number;
        low: number;
        applied: number;
    };
};

async function postAction(action: string, payload: Record<string, unknown>) {
    const response = await fetch(`/api.php?action=${action}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN':
                document
                    .querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                    ?.getAttribute('content') ?? '',
            Accept: 'application/json',
        },
        body: JSON.stringify(payload),
    });

    if (!response.ok) {
        const data = await response.json().catch(() => null);

        throw new Error(
            data?.message || data?.error || 'Dashboard update failed.',
        );
    }
}

function pageLabel(label: string) {
    return label
        .replace('&laquo;', '')
        .replace('&raquo;', '')
        .replace('Previous', 'Previous')
        .replace('Next', 'Next')
        .trim();
}

export default function Dashboard({
    jobs,
    topJobs,
    filters,
    workflowStatuses,
    applicationStatuses,
    runStatuses,
    metrics,
}: DashboardProps) {
    const [query, setQuery] = useState(filters.q ?? '');
    const [status, setStatus] = useState(filters.status ?? 'all');
    const [bucket, setBucket] = useState(filters.bucket ?? 'all');
    const [view, setView] = useState(filters.view ?? 'compact');
    const [busyJobId, setBusyJobId] = useState<number | null>(null);
    const [selectedJob, setSelectedJob] = useState<JobSummary | null>(null);
    const [drawerOpen, setDrawerOpen] = useState(false);
    const firstRender = useRef(true);

    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;

            return;
        }

        const timeout = window.setTimeout(() => {
            router.get(
                '/dashboard',
                { q: query, status, bucket, view },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only: ['jobs', 'metrics', 'filters'],
                },
            );
        }, 350);

        return () => window.clearTimeout(timeout);
    }, [bucket, query, status, view]);

    const metricTiles: Array<{
        Icon: LucideIcon;
        label: string;
        value: number;
    }> = [
        { label: 'All Jobs', value: metrics.all, Icon: BriefcaseBusiness },
        { label: 'High Fit', value: metrics.high, Icon: Gauge },
        { label: 'Medium Fit', value: metrics.medium, Icon: Sparkles },
        { label: 'Low Fit', value: metrics.low, Icon: Search },
        { label: 'Applied', value: metrics.applied, Icon: CheckCircle2 },
    ];

    function openReview(job: JobSummary) {
        setSelectedJob(job);
        setDrawerOpen(true);
    }

    async function updateStatus(jobId: number, nextStatus: string) {
        setBusyJobId(jobId);

        try {
            await postAction('status', { id: jobId, status: nextStatus });
            router.reload({ only: ['jobs', 'topJobs', 'metrics'] });
        } finally {
            setBusyJobId(null);
        }
    }

    async function updateApplicationStatus(
        jobId: number,
        applicationStatus: string,
    ) {
        setBusyJobId(jobId);

        try {
            await postAction('application', {
                id: jobId,
                application_status: applicationStatus,
            });
            router.reload({ only: ['jobs', 'topJobs', 'metrics'] });
        } finally {
            setBusyJobId(null);
        }
    }

    async function requestGenerate(jobId: number) {
        setBusyJobId(jobId);

        try {
            await postAction('generate', { id: jobId });
            router.reload({ only: ['jobs', 'topJobs', 'metrics'] });
        } finally {
            setBusyJobId(null);
        }
    }

    return (
        <>
            <Head title="Job Dashboard" />
            <main className="flex h-full flex-1 flex-col gap-5 overflow-x-auto p-4 md:p-6">
                <section className="flex flex-col gap-3 border-b pb-5 md:flex-row md:items-end md:justify-between">
                    <div>
                        <p className="text-sm font-medium text-muted-foreground">
                            Job Search Dashboard V2
                        </p>
                        <h1 className="text-2xl font-semibold tracking-normal text-foreground">
                            Applications, fit scores, and review queue
                        </h1>
                    </div>
                    <Button
                        variant="outline"
                        onClick={() => router.reload()}
                        className="w-fit"
                    >
                        <RefreshCw />
                        Refresh
                    </Button>
                </section>

                <TopJobsPanel jobs={topJobs} onReview={openReview} />

                <section className="grid gap-3 md:grid-cols-5">
                    {metricTiles.map(({ Icon, label, value }) => (
                        <div
                            key={label}
                            className="rounded-md border bg-background p-4"
                        >
                            <div className="flex items-center justify-between gap-3">
                                <span className="text-sm text-muted-foreground">
                                    {label}
                                </span>
                                <Icon className="size-4 text-muted-foreground" />
                            </div>
                            <strong className="mt-2 block text-2xl">
                                {value}
                            </strong>
                        </div>
                    ))}
                </section>

                <FilterToolbar
                    query={query}
                    status={status}
                    bucket={bucket}
                    view={view}
                    workflowStatuses={workflowStatuses}
                    onQueryChange={setQuery}
                    onStatusChange={setStatus}
                    onBucketChange={setBucket}
                    onViewChange={setView}
                />

                <section className="grid gap-3 md:grid-cols-3">
                    {['last_sync', 'last_discovery', 'last_generate'].map(
                        (name) => {
                            const run = runStatuses[name];

                            return (
                                <div
                                    key={name}
                                    className="rounded-md border p-3"
                                >
                                    <span className="text-xs font-medium text-muted-foreground">
                                        {name
                                            .replaceAll('_', ' ')
                                            .toUpperCase()}
                                    </span>
                                    <p className="mt-1 text-sm">
                                        {run?.last_run_at || 'Not run'}{' '}
                                        {run?.status ? `(${run.status})` : ''}
                                    </p>
                                    {run?.details && (
                                        <p className="mt-1 line-clamp-2 text-xs text-muted-foreground">
                                            {run.details}
                                        </p>
                                    )}
                                </div>
                            );
                        },
                    )}
                </section>

                <section className="flex flex-col gap-3">
                    <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                        <h2 className="text-base font-semibold">
                            {jobs.total} matching job
                            {jobs.total === 1 ? '' : 's'}
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            Showing {jobs.from ?? 0}-{jobs.to ?? 0} of{' '}
                            {jobs.total}
                        </p>
                    </div>

                    {jobs.data.map((job) => (
                        <JobCard
                            key={job.id}
                            job={job}
                            view={view}
                            busy={busyJobId === job.id}
                            workflowStatuses={workflowStatuses}
                            applicationStatuses={applicationStatuses}
                            onReview={openReview}
                            onGenerate={requestGenerate}
                            onStatusChange={updateStatus}
                            onApplicationStatusChange={
                                updateApplicationStatus
                            }
                        />
                    ))}

                    {jobs.last_page > 1 && (
                        <div className="flex flex-wrap gap-2 pt-2">
                            {jobs.links.map((link) => (
                                <Button
                                    key={`${link.label}-${link.url}`}
                                    asChild={Boolean(link.url)}
                                    variant={link.active ? 'default' : 'outline'}
                                    size="sm"
                                    disabled={!link.url}
                                >
                                    {link.url ? (
                                        <Link
                                            href={link.url}
                                            preserveState
                                            preserveScroll
                                            only={[
                                                'jobs',
                                                'metrics',
                                                'filters',
                                            ]}
                                        >
                                            {pageLabel(link.label)}
                                        </Link>
                                    ) : (
                                        <span>{pageLabel(link.label)}</span>
                                    )}
                                </Button>
                            ))}
                        </div>
                    )}
                </section>
            </main>
            <JobDetailsDrawer
                job={selectedJob}
                open={drawerOpen}
                onOpenChange={setDrawerOpen}
            />
            <FloatingBackToTopButton />
        </>
    );
}
