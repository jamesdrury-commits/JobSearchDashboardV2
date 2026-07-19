import type { ReactNode } from 'react';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import type { JobSummary } from '@/types/dashboard';

type JobSignalPillsProps = {
    job: Pick<
        JobSummary,
        | 'benefits_pension_notes'
        | 'notes'
        | 'remote_status'
        | 'resume_angle'
        | 'role'
        | 'salary'
        | 'tradeoffs_watch_outs'
        | 'why_considering'
    > & { description?: string | null };
    compact?: boolean;
};

type Signal = {
    label: string;
    tone: 'good' | 'warn' | 'bad' | 'neutral';
    title: string;
};

export function JobSignalPills({ job, compact = false }: JobSignalPillsProps) {
    const signals = jobSignals(job);

    if (signals.length === 0) {
        return null;
    }

    return (
        <div className="flex flex-wrap gap-1.5" aria-label="Job fit signals">
            {signals.map((signal) => (
                <Badge
                    key={signal.label}
                    variant="outline"
                    title={signal.title}
                    className={cn(
                        'cursor-default select-none',
                        compact ? 'px-1.5 py-0 text-[11px]' : '',
                        signalClass(signal.tone),
                    )}
                >
                    {signal.label}
                </Badge>
            ))}
        </div>
    );
}

export function InfoPill({
    children,
    title,
}: {
    children: ReactNode;
    title: string;
}) {
    return (
        <Badge
            variant="outline"
            title={title}
            className="cursor-default border-slate-200 bg-slate-50 text-slate-700 select-none dark:border-slate-800 dark:bg-slate-950 dark:text-slate-300"
        >
            {children}
        </Badge>
    );
}

function jobSignals(job: JobSignalPillsProps['job']): Signal[] {
    const text = [
        job.benefits_pension_notes,
        job.description,
        job.notes,
        job.remote_status,
        job.resume_angle,
        job.role,
        job.salary,
        job.tradeoffs_watch_outs,
        job.why_considering,
    ]
        .filter(Boolean)
        .join(' ')
        .toLowerCase();

    const signals: Signal[] = [];

    if (/\bremote\b/.test(text)) {
        signals.push({
            label: 'Remote',
            tone: 'good',
            title: 'Remote work is mentioned in the imported job data.',
        });
    } else if (/\bhybrid\b/.test(text)) {
        signals.push({
            label: 'Hybrid',
            tone: 'warn',
            title: 'Hybrid work is mentioned; review commute expectations.',
        });
    } else if (/\bon[- ]?site\b|\bin office\b/.test(text)) {
        signals.push({
            label: 'On-site',
            tone: 'bad',
            title: 'On-site work is mentioned; check commute and relocation risk.',
        });
    }

    if (
        /\bon[- ]?call\b|\bafter[- ]?hours\b|\bweekend\b|\b24\/7\b|\b24x7\b/.test(
            text,
        )
    ) {
        signals.push({
            label: 'After-hours risk',
            tone: 'bad',
            title: 'On-call, weekend, 24/7, or after-hours language is present.',
        });
    }

    if (/\bsalesforce\b|\bsfdc\b/.test(text)) {
        signals.push({
            label: 'Salesforce',
            tone: 'warn',
            title: 'Salesforce appears in this job. Visible, but not meant to be over-emphasized.',
        });
    }

    if (/\bsiebel\b/.test(text)) {
        signals.push({
            label: 'Siebel / CRM',
            tone: 'good',
            title: 'Siebel or CRM experience is explicitly relevant.',
        });
    }

    if (/\bbenefit|\bpension|\bretirement|\b401k|\b401\(k\)/.test(text)) {
        signals.push({
            label: 'Benefits noted',
            tone: 'good',
            title: 'Benefits, pension, retirement, or 401(k) language is present.',
        });
    }

    if (
        /\$ ?1[2-9][5-9]k|\$ ?[2-9]\d\dk|\$ ?1[2-9][5-9],000|\$ ?[2-9]\d\d,000/.test(
            text,
        )
    ) {
        signals.push({
            label: '$125k+',
            tone: 'good',
            title: 'Compensation appears to meet or exceed the normal minimum.',
        });
    }

    return dedupeSignals(signals).slice(0, 6);
}

function dedupeSignals(signals: Signal[]): Signal[] {
    return signals.filter(
        (signal, index) =>
            signals.findIndex(
                (candidate) => candidate.label === signal.label,
            ) === index,
    );
}

function signalClass(tone: Signal['tone']): string {
    if (tone === 'good') {
        return 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200';
    }

    if (tone === 'bad') {
        return 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-900 dark:bg-rose-950 dark:text-rose-200';
    }

    if (tone === 'warn') {
        return 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200';
    }

    return 'border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-300';
}
