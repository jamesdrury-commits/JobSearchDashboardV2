import { Head, router } from '@inertiajs/react';
import {
    BriefcaseBusiness,
    CheckCircle2,
    FileText,
    Gauge,
    RefreshCw,
    Search,
    Sparkles,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type GeneratedDocument = {
    id: number;
    document_type: string;
    v1_reference: string | null;
    stored_path: string | null;
};

type Job = {
    id: number;
    company: string;
    role: string;
    url: string | null;
    salary: string;
    remote_status: string;
    match_score: number;
    career_fit_score: number;
    life_fit_score: number;
    overall_recommendation: string;
    why_considering: string | null;
    tradeoffs_watch_outs: string | null;
    local_exception_reason: string | null;
    commute_notes: string | null;
    benefits_pension_notes: string | null;
    resume_angle: string | null;
    source_lane: string;
    executive_watch: boolean;
    status: string;
    source: string;
    notes: string | null;
    resume_file: string | null;
    cover_letter_file: string | null;
    review_summary_file: string | null;
    generated_docs_summary: string | null;
    application_status: string;
    application_missing_fields: string | null;
    application_warnings: string | null;
    application_review_file: string | null;
    generated_documents?: GeneratedDocument[];
};

type RunStatus = {
    run_name: string;
    last_run_at: string;
    status: string;
    details: string | null;
};

type DashboardProps = {
    jobs: Job[];
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

const workflowBuckets = [
    { label: 'All', value: 'all' },
    { label: 'Queue', value: 'queue' },
    { label: 'Interested', value: 'interested' },
    { label: 'Applied', value: 'applied' },
    { label: 'Review', value: 'review' },
];

function recommendationClass(value: string) {
    const normalized = value.toLowerCase();

    if (normalized === 'apply') {
        return 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200';
    }

    if (normalized === 'pass') {
        return 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-900 dark:bg-rose-950 dark:text-rose-200';
    }

    return 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200';
}

function scoreClass(score: number) {
    if (score >= 80) {
        return 'text-emerald-700 dark:text-emerald-300';
    }

    if (score >= 55) {
        return 'text-amber-700 dark:text-amber-300';
    }

    return 'text-rose-700 dark:text-rose-300';
}

function splitNotes(value: string | null) {
    return String(value ?? '')
        .split(/[;\n\r]+/)
        .map((part) => part.trim())
        .filter(Boolean);
}

async function postAction(action: string, payload: Record<string, unknown>) {
    const response = await fetch(`/api.php?action=${action}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
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

export default function Dashboard({
    jobs,
    workflowStatuses,
    applicationStatuses,
    runStatuses,
    metrics,
}: DashboardProps) {
    const [query, setQuery] = useState('');
    const [status, setStatus] = useState('all');
    const [bucket, setBucket] = useState('all');
    const [busyJobId, setBusyJobId] = useState<number | null>(null);
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

    const filteredJobs = useMemo(() => {
        const q = query.trim().toLowerCase();

        return jobs.filter((job) => {
            const haystack = [
                job.company,
                job.role,
                job.salary,
                job.remote_status,
                job.source,
                job.source_lane,
                job.notes,
                job.why_considering,
                job.tradeoffs_watch_outs,
                job.resume_angle,
                job.application_status,
            ]
                .join(' ')
                .toLowerCase();

            if (q && !haystack.includes(q)) {
                return false;
            }

            if (status !== 'all' && job.status !== status) {
                return false;
            }

            if (bucket === 'queue') {
                return [
                    'Apply Soon',
                    'Interested',
                    'Generate Requested',
                    'Ready for Review',
                ].includes(job.status);
            }

            if (bucket === 'interested') {
                return ['Interested', 'Apply Soon'].includes(job.status);
            }

            if (bucket === 'applied') {
                return (
                    ['Applied', 'Submitted'].includes(job.status) ||
                    job.application_status === 'Submitted'
                );
            }

            if (bucket === 'review') {
                return (
                    [
                        'Ready for Review',
                        'Needs Manual Review',
                        'Needs Follow-up',
                    ].includes(job.status) ||
                    [
                        'Ready for Review',
                        'Needs Manual Review',
                        'Needs Follow-up',
                    ].includes(job.application_status)
                );
            }

            return true;
        });
    }, [bucket, jobs, query, status]);

    async function updateStatus(jobId: number, nextStatus: string) {
        setBusyJobId(jobId);
        await postAction('status', { id: jobId, status: nextStatus });
        router.reload({ only: ['jobs', 'metrics'] });
        setBusyJobId(null);
    }

    async function updateApplicationStatus(
        jobId: number,
        applicationStatus: string,
    ) {
        setBusyJobId(jobId);
        await postAction('application', {
            id: jobId,
            application_status: applicationStatus,
        });
        router.reload({ only: ['jobs', 'metrics'] });
        setBusyJobId(null);
    }

    async function requestGenerate(jobId: number) {
        setBusyJobId(jobId);
        await postAction('generate', { id: jobId });
        router.reload({ only: ['jobs', 'metrics'] });
        setBusyJobId(null);
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

                <section className="grid gap-3 rounded-md border bg-muted/30 p-3 md:grid-cols-[1fr_auto_auto]">
                    <div className="relative">
                        <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={query}
                            onChange={(event) => setQuery(event.target.value)}
                            className="pl-9"
                            placeholder="Search company, role, notes, salary, source"
                        />
                    </div>
                    <Select value={status} onValueChange={setStatus}>
                        <SelectTrigger className="w-full md:w-[220px]">
                            <SelectValue placeholder="All statuses" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All statuses</SelectItem>
                            {workflowStatuses.map((item) => (
                                <SelectItem key={item} value={item}>
                                    {item}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select value={bucket} onValueChange={setBucket}>
                        <SelectTrigger className="w-full md:w-[160px]">
                            <SelectValue placeholder="Workflow" />
                        </SelectTrigger>
                        <SelectContent>
                            {workflowBuckets.map((item) => (
                                <SelectItem key={item.value} value={item.value}>
                                    {item.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </section>

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
                    <div className="flex items-center justify-between">
                        <h2 className="text-base font-semibold">
                            {filteredJobs.length} visible job
                            {filteredJobs.length === 1 ? '' : 's'}
                        </h2>
                    </div>

                    {filteredJobs.map((job) => {
                        const noteParts = splitNotes(job.notes);
                        const documentReferences = [
                            ['Resume', job.resume_file],
                            ['Cover letter', job.cover_letter_file],
                            ['Review summary', job.review_summary_file],
                            ['Application review', job.application_review_file],
                        ].filter(([, value]) => value);

                        return (
                            <article
                                key={job.id}
                                className="rounded-md border bg-background p-4 shadow-xs"
                            >
                                <div className="grid gap-4 lg:grid-cols-[1fr_auto]">
                                    <div className="min-w-0">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <Badge variant="outline">
                                                {job.source_lane ||
                                                    job.source ||
                                                    'Job board'}
                                            </Badge>
                                            <Badge
                                                variant="outline"
                                                className={recommendationClass(
                                                    job.overall_recommendation ||
                                                        'Maybe',
                                                )}
                                            >
                                                {job.overall_recommendation ||
                                                    'Maybe'}
                                            </Badge>
                                            {job.executive_watch && (
                                                <Badge variant="secondary">
                                                    Early operations watch
                                                </Badge>
                                            )}
                                            <Badge variant="outline">
                                                {job.status}
                                            </Badge>
                                            <Badge variant="outline">
                                                {job.application_status ||
                                                    'Not Started'}
                                            </Badge>
                                        </div>
                                        <h3 className="mt-3 text-lg font-semibold">
                                            {job.company}
                                        </h3>
                                        <p className="text-base text-muted-foreground">
                                            {job.role}
                                        </p>
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            {job.salary || 'Salary unknown'} -{' '}
                                            {job.remote_status ||
                                                'Location unknown'}
                                        </p>
                                    </div>

                                    <div className="grid grid-cols-2 gap-2 sm:w-[220px]">
                                        <div className="rounded-md border p-3 text-center">
                                            <strong
                                                className={`block text-2xl ${scoreClass(job.career_fit_score)}`}
                                            >
                                                {job.career_fit_score}
                                            </strong>
                                            <span className="text-xs text-muted-foreground">
                                                Career Fit
                                            </span>
                                        </div>
                                        <div className="rounded-md border p-3 text-center">
                                            <strong
                                                className={`block text-2xl ${scoreClass(job.life_fit_score)}`}
                                            >
                                                {job.life_fit_score}
                                            </strong>
                                            <span className="text-xs text-muted-foreground">
                                                Life Fit
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div className="mt-4 grid gap-3 lg:grid-cols-2">
                                    {[
                                        ['Why', job.why_considering],
                                        ['Tradeoffs', job.tradeoffs_watch_outs],
                                        [
                                            'Local exception',
                                            job.local_exception_reason,
                                        ],
                                        ['Commute', job.commute_notes],
                                        [
                                            'Benefits / Pension',
                                            job.benefits_pension_notes,
                                        ],
                                        ['Resume angle', job.resume_angle],
                                    ]
                                        .filter(([, value]) => value)
                                        .map(([label, value]) => (
                                            <div
                                                key={label}
                                                className="rounded-md bg-muted/40 p-3"
                                            >
                                                <span className="text-xs font-medium text-muted-foreground">
                                                    {label}
                                                </span>
                                                <p className="mt-1 text-sm">
                                                    {value}
                                                </p>
                                            </div>
                                        ))}
                                </div>

                                {noteParts.length > 0 && (
                                    <ul className="mt-4 grid gap-1 text-sm text-muted-foreground md:grid-cols-2">
                                        {noteParts.slice(0, 8).map((note) => (
                                            <li key={note}>- {note}</li>
                                        ))}
                                    </ul>
                                )}

                                {documentReferences.length > 0 && (
                                    <div className="mt-4 flex flex-wrap gap-2">
                                        {documentReferences.map(
                                            ([label, value]) => (
                                                <a
                                                    key={`${label}-${value}`}
                                                    href={`/api.php?action=file&path=${encodeURIComponent(String(value))}`}
                                                    className="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm hover:bg-accent"
                                                >
                                                    <FileText className="size-4" />
                                                    {label}
                                                </a>
                                            ),
                                        )}
                                    </div>
                                )}

                                {job.generated_docs_summary && (
                                    <p className="mt-3 rounded-md bg-muted/40 p-3 text-sm text-muted-foreground">
                                        {job.generated_docs_summary}
                                    </p>
                                )}

                                <div className="mt-4 flex flex-wrap gap-2 border-t pt-4">
                                    <Button
                                        size="sm"
                                        onClick={() => requestGenerate(job.id)}
                                        disabled={busyJobId === job.id}
                                    >
                                        <Sparkles />
                                        Generate
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={() =>
                                            updateStatus(job.id, 'Interested')
                                        }
                                        disabled={busyJobId === job.id}
                                    >
                                        Interested
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={() =>
                                            updateStatus(job.id, 'Pass')
                                        }
                                        disabled={busyJobId === job.id}
                                    >
                                        Pass
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={() =>
                                            updateStatus(job.id, 'Applied')
                                        }
                                        disabled={busyJobId === job.id}
                                    >
                                        Applied
                                    </Button>
                                    <Select
                                        value={
                                            job.application_status ||
                                            'Not Started'
                                        }
                                        onValueChange={(value) =>
                                            updateApplicationStatus(
                                                job.id,
                                                value,
                                            )
                                        }
                                    >
                                        <SelectTrigger className="h-8 w-[190px]">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {applicationStatuses.map((item) => (
                                                <SelectItem
                                                    key={item}
                                                    value={item}
                                                >
                                                    {item}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </article>
                        );
                    })}
                </section>
            </main>
        </>
    );
}
