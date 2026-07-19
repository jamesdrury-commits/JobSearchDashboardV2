import { ExternalLink, LoaderCircle } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import type { JobDetail, JobSummary } from '@/types/dashboard';
import { ApplicationTimeline } from './ApplicationTimeline';
import { DocumentPanel } from './DocumentPanel';
import { ScoreExplanation } from './ScoreExplanation';
import { splitNotes } from './score-utils';

type JobDetailsDrawerProps = {
    job: JobSummary | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

export function JobDetailsDrawer({
    job,
    open,
    onOpenChange,
}: JobDetailsDrawerProps) {
    const [detail, setDetail] = useState<JobDetail | null>(null);
    const [error, setError] = useState<{
        jobId: number;
        message: string;
    } | null>(null);

    useEffect(() => {
        if (!open || !job) {
            return;
        }

        const controller = new AbortController();

        fetch(`/dashboard/jobs/${job.id}`, {
            headers: { Accept: 'application/json' },
            signal: controller.signal,
        })
            .then(async (response) => {
                if (!response.ok) {
                    throw new Error('Unable to load job details.');
                }

                return (await response.json()) as JobDetail;
            })
            .then((loaded) => {
                setDetail(loaded);
                setError(null);
            })
            .catch((thrown: unknown) => {
                if (!controller.signal.aborted) {
                    setError({
                        jobId: job.id,
                        message:
                            thrown instanceof Error
                                ? thrown.message
                                : 'Unable to load job details.',
                    });
                }
            });

        return () => controller.abort();
    }, [job, open]);

    const loadedDetail = detail?.id === job?.id ? detail : null;
    const shownError = error && job && error.jobId === job.id ? error.message : null;
    const loading = Boolean(open && job && !loadedDetail && !shownError);
    const shown = loadedDetail ?? job;

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent className="w-full overflow-y-auto sm:max-w-2xl">
                <SheetHeader>
                    <SheetTitle>{shown?.company ?? 'Job details'}</SheetTitle>
                    <SheetDescription>{shown?.role}</SheetDescription>
                </SheetHeader>

                {!shown ? null : (
                    <div className="space-y-6 px-4 pb-6">
                        <div className="flex flex-wrap gap-2">
                            {shown.url && (
                                <Button asChild size="sm">
                                    <a
                                        href={shown.url}
                                        target="_blank"
                                        rel="noreferrer"
                                    >
                                        <ExternalLink />
                                        Open Posting
                                    </a>
                                </Button>
                            )}
                            <span className="rounded-md border px-3 py-1.5 text-sm">
                                {shown.salary || 'Salary unknown'}
                            </span>
                            <span className="rounded-md border px-3 py-1.5 text-sm">
                                {shown.remote_status || 'Location unknown'}
                            </span>
                        </div>

                        {loading && (
                            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                <LoaderCircle className="size-4 animate-spin" />
                                Loading full details
                            </div>
                        )}

                        {shownError && (
                            <p className="rounded-md border border-destructive/30 bg-destructive/10 p-3 text-sm text-destructive">
                                {shownError}
                            </p>
                        )}

                        {loadedDetail && (
                            <>
                                <ScoreExplanation job={loadedDetail} />

                                <section className="space-y-3">
                                    <h3 className="text-sm font-semibold">
                                        Notes and fit context
                                    </h3>
                                    <div className="grid gap-3">
                                        {[
                                            ['Why', loadedDetail.why_considering],
                                            [
                                                'Tradeoffs',
                                                loadedDetail.tradeoffs_watch_outs,
                                            ],
                                            [
                                                'Local exception',
                                                loadedDetail.local_exception_reason,
                                            ],
                                            ['Commute', loadedDetail.commute_notes],
                                            [
                                                'Benefits / Pension',
                                                loadedDetail.benefits_pension_notes,
                                            ],
                                            [
                                                'Resume angle',
                                                loadedDetail.resume_angle,
                                            ],
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
                                    {splitNotes(loadedDetail.notes).length >
                                        0 && (
                                        <ul className="space-y-1 text-sm text-muted-foreground">
                                            {splitNotes(loadedDetail.notes).map(
                                                (note) => (
                                                    <li key={note}>- {note}</li>
                                                ),
                                            )}
                                        </ul>
                                    )}
                                    {loadedDetail.job_notes.map((note) => (
                                        <p
                                            key={note.id}
                                            className="rounded-md border p-3 text-sm"
                                        >
                                            {note.body_markdown}
                                        </p>
                                    ))}
                                </section>

                                <DocumentPanel job={loadedDetail} />

                                <section className="space-y-3">
                                    <h3 className="text-sm font-semibold">
                                        Full job description
                                    </h3>
                                    <p className="whitespace-pre-wrap rounded-md bg-muted/40 p-3 text-sm">
                                        {loadedDetail.description ||
                                            'No full description is stored yet.'}
                                    </p>
                                </section>

                                <ApplicationTimeline job={loadedDetail} />
                            </>
                        )}
                    </div>
                )}
            </SheetContent>
        </Sheet>
    );
}
