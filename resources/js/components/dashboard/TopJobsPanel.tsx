import { ExternalLink, Trophy } from 'lucide-react';
import { Button } from '@/components/ui/button';
import type { JobSummary } from '@/types/dashboard';
import { InfoPill, JobSignalPills } from './JobSignalPills';
import { RecommendationBadge } from './ScoreBadge';
import { scoreClass } from './score-utils';

type TopJobsPanelProps = {
    jobs: JobSummary[];
    onReview: (job: JobSummary) => void;
};

export function TopJobsPanel({ jobs, onReview }: TopJobsPanelProps) {
    const columns = [
        { title: 'Apply First', jobs: jobs.slice(0, 10), offset: 0 },
        { title: 'Second Look', jobs: jobs.slice(10, 20), offset: 10 },
    ];

    return (
        <section className="rounded-md border border-sky-200 bg-sky-50/45 dark:border-sky-950 dark:bg-sky-950/20">
            <div className="flex flex-col gap-2 border-b p-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <div className="flex items-center gap-2 text-sm font-medium text-muted-foreground">
                        <Trophy className="size-4" />
                        Top 20 Dashboard
                    </div>
                    <h2 className="text-lg font-semibold">
                        Best current opportunities
                    </h2>
                </div>
                <span className="text-sm text-muted-foreground">
                    Ranked from imported V1 data and this account's scoring
                    settings
                </span>
            </div>
            <div className="grid gap-4 p-4 lg:grid-cols-2">
                {columns.map((column) => (
                    <div key={column.title} className="space-y-3">
                        <h3 className="text-base font-semibold text-sky-950 dark:text-sky-100">
                            {column.title}
                        </h3>
                        <div className="space-y-2">
                            {column.jobs.map((job, index) => (
                                <TopJobRow
                                    key={job.id}
                                    job={job}
                                    rank={column.offset + index + 1}
                                    onReview={onReview}
                                />
                            ))}
                        </div>
                    </div>
                ))}
            </div>
        </section>
    );
}

function TopJobRow({
    job,
    rank,
    onReview,
}: {
    job: JobSummary;
    rank: number;
    onReview: (job: JobSummary) => void;
}) {
    return (
        <article className="rounded-md border border-sky-200 bg-background p-3 shadow-xs dark:border-sky-950">
            <div className="grid gap-3 sm:grid-cols-[1fr_auto]">
                <div className="min-w-0">
                    <div className="flex items-start gap-2">
                        <span className="shrink-0 text-sm font-semibold text-sky-900 dark:text-sky-100">
                            {rank}.
                        </span>
                        <div className="min-w-0">
                            <p className="truncate font-semibold">
                                {job.company}
                            </p>
                            <p className="truncate text-sm text-muted-foreground">
                                {job.role}
                            </p>
                        </div>
                    </div>
                    <p className="mt-2 truncate text-sm font-medium text-muted-foreground">
                        {job.salary || 'Salary unknown'} -{' '}
                        {job.remote_status || 'Location unknown'}
                    </p>
                </div>
                <div className="text-left sm:text-right">
                    <strong
                        className={`block text-xl leading-none ${scoreClass(job.priority_score)}`}
                    >
                        {job.priority_score}
                    </strong>
                    <span className="text-xs font-medium text-muted-foreground">
                        Career {job.career_fit_score} / Life{' '}
                        {job.life_fit_score}
                    </span>
                </div>
            </div>

            <div className="mt-3 flex flex-wrap items-center gap-2">
                <RecommendationBadge
                    value={job.overall_recommendation || 'Maybe'}
                />
                <InfoPill title="Current workflow status. Change it from the More Actions menu in the full list.">
                    Status: {job.status}
                </InfoPill>
                {job.source_lane && (
                    <InfoPill title="Imported source or sourcing lane. This is informational.">
                        Source: {job.source_lane}
                    </InfoPill>
                )}
                <JobSignalPills job={job} compact />
                <div className="ml-auto flex flex-wrap gap-2">
                    {job.url && (
                        <Button asChild size="sm" variant="outline">
                            <a href={job.url} target="_blank" rel="noreferrer">
                                <ExternalLink />
                                Open Posting
                            </a>
                        </Button>
                    )}
                    <Button
                        size="sm"
                        variant="outline"
                        onClick={() => onReview(job)}
                    >
                        Review Package
                    </Button>
                </div>
            </div>
        </article>
    );
}
