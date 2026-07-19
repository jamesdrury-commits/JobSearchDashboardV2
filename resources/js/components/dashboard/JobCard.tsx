import {
    CheckCircle2,
    ExternalLink,
    MoreHorizontal,
    Sparkles,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuSub,
    DropdownMenuSubContent,
    DropdownMenuSubTrigger,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { JobSummary } from '@/types/dashboard';
import { PriorityScore } from './PriorityScore';
import { RecommendationBadge, ScoreBadge } from './ScoreBadge';
import { splitNotes } from './score-utils';
import { WorkflowStatus } from './WorkflowStatus';

type JobCardProps = {
    job: JobSummary;
    view: string;
    busy: boolean;
    workflowStatuses: string[];
    applicationStatuses: string[];
    onReview: (job: JobSummary) => void;
    onGenerate: (jobId: number) => void;
    onStatusChange: (jobId: number, status: string) => void;
    onApplicationStatusChange: (jobId: number, status: string) => void;
};

export function JobCard({
    job,
    view,
    busy,
    workflowStatuses,
    applicationStatuses,
    onReview,
    onGenerate,
    onStatusChange,
    onApplicationStatusChange,
}: JobCardProps) {
    const noteParts = splitNotes(job.notes);
    const detailRows = [
        ['Why', job.why_considering],
        ['Tradeoffs', job.tradeoffs_watch_outs],
        ['Local exception', job.local_exception_reason],
        ['Commute', job.commute_notes],
        ['Benefits / Pension', job.benefits_pension_notes],
        ['Resume angle', job.resume_angle],
    ].filter(([, value]) => value);

    return (
        <article className="rounded-md border bg-background p-4 shadow-xs">
            <div className="grid gap-4 xl:grid-cols-[1fr_auto]">
                <div className="min-w-0">
                    <div className="flex flex-wrap items-center gap-2">
                        <Badge variant="outline">
                            {job.source_lane || job.source || 'Job board'}
                        </Badge>
                        <RecommendationBadge
                            value={job.overall_recommendation || 'Maybe'}
                        />
                        {job.executive_watch && (
                            <Badge variant="secondary">
                                Early operations watch
                            </Badge>
                        )}
                        <WorkflowStatus
                            status={job.status}
                            applicationStatus={job.application_status}
                        />
                        {job.document_count > 0 && (
                            <Badge variant="outline">
                                {job.document_count} docs
                            </Badge>
                        )}
                    </div>
                    <h3 className="mt-3 text-lg font-semibold">
                        {job.company}
                    </h3>
                    <p className="text-base text-muted-foreground">
                        {job.role}
                    </p>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {job.salary || 'Salary unknown'} -{' '}
                        {job.remote_status || 'Location unknown'}
                    </p>
                </div>

                <div className="grid grid-cols-3 gap-2 sm:w-[340px]">
                    <PriorityScore score={job.priority_score} />
                    <ScoreBadge
                        label="Career Fit"
                        score={job.career_fit_score}
                    />
                    <ScoreBadge label="Life Fit" score={job.life_fit_score} />
                </div>
            </div>

            {view === 'detailed' && detailRows.length > 0 && (
                <div className="mt-4 grid gap-3 lg:grid-cols-2">
                    {detailRows.map(([label, value]) => (
                        <div key={label} className="rounded-md bg-muted/40 p-3">
                            <span className="text-xs font-medium text-muted-foreground">
                                {label}
                            </span>
                            <p className="mt-1 line-clamp-3 text-sm">
                                {value}
                            </p>
                        </div>
                    ))}
                </div>
            )}

            {view === 'detailed' && noteParts.length > 0 && (
                <ul className="mt-4 grid gap-1 text-sm text-muted-foreground md:grid-cols-2">
                    {noteParts.slice(0, 6).map((note) => (
                        <li key={note}>- {note}</li>
                    ))}
                </ul>
            )}

            <div className="mt-4 flex flex-wrap gap-2 border-t pt-4">
                {job.url && (
                    <Button asChild size="sm">
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
                <Button
                    size="sm"
                    variant="outline"
                    onClick={() => onStatusChange(job.id, 'Applied')}
                    disabled={busy}
                >
                    <CheckCircle2 />
                    Apply
                </Button>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button size="sm" variant="ghost" disabled={busy}>
                            <MoreHorizontal />
                            More Actions
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" className="w-56">
                        <DropdownMenuLabel>Workflow</DropdownMenuLabel>
                        <DropdownMenuItem
                            onSelect={() => onStatusChange(job.id, 'Interested')}
                        >
                            Interested
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            onSelect={() => onStatusChange(job.id, 'Pass')}
                        >
                            Pass
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            onSelect={() => onGenerate(job.id)}
                        >
                            <Sparkles />
                            Generate package
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuSub>
                            <DropdownMenuSubTrigger>
                                Set workflow status
                            </DropdownMenuSubTrigger>
                            <DropdownMenuSubContent>
                                {workflowStatuses.map((status) => (
                                    <DropdownMenuItem
                                        key={status}
                                        onSelect={() =>
                                            onStatusChange(job.id, status)
                                        }
                                    >
                                        {status}
                                    </DropdownMenuItem>
                                ))}
                            </DropdownMenuSubContent>
                        </DropdownMenuSub>
                        <DropdownMenuSub>
                            <DropdownMenuSubTrigger>
                                Application status
                            </DropdownMenuSubTrigger>
                            <DropdownMenuSubContent>
                                {applicationStatuses.map((status) => (
                                    <DropdownMenuItem
                                        key={status}
                                        onSelect={() =>
                                            onApplicationStatusChange(
                                                job.id,
                                                status,
                                            )
                                        }
                                    >
                                        {status}
                                    </DropdownMenuItem>
                                ))}
                            </DropdownMenuSubContent>
                        </DropdownMenuSub>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </article>
    );
}
