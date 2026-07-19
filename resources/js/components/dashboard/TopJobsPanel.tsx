import { ExternalLink, Trophy } from 'lucide-react';
import { Button } from '@/components/ui/button';
import type { JobSummary } from '@/types/dashboard';
import { PriorityScore } from './PriorityScore';

type TopJobsPanelProps = {
    jobs: JobSummary[];
    onReview: (job: JobSummary) => void;
};

export function TopJobsPanel({ jobs, onReview }: TopJobsPanelProps) {
    return (
        <section className="rounded-md border bg-background">
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
            <div className="divide-y">
                {jobs.map((job, index) => (
                    <div
                        key={job.id}
                        className="grid gap-3 p-3 md:grid-cols-[44px_1fr_auto] md:items-center"
                    >
                        <div className="text-sm font-semibold text-muted-foreground">
                            #{index + 1}
                        </div>
                        <div className="min-w-0">
                            <p className="truncate font-medium">
                                {job.company}
                            </p>
                            <p className="truncate text-sm text-muted-foreground">
                                {job.role}
                            </p>
                        </div>
                        <div className="flex flex-wrap items-center gap-2">
                            <PriorityScore score={job.priority_score} />
                            {job.url && (
                                <Button asChild size="sm" variant="outline">
                                    <a
                                        href={job.url}
                                        target="_blank"
                                        rel="noreferrer"
                                    >
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
                ))}
            </div>
        </section>
    );
}
