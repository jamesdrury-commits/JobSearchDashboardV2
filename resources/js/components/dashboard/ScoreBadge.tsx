import { Badge } from '@/components/ui/badge';
import { scoreClass } from './score-utils';

type ScoreBadgeProps = {
    label: string;
    score: number;
};

export function ScoreBadge({ label, score }: ScoreBadgeProps) {
    return (
        <div className="rounded-md border p-3 text-center">
            <strong className={`block text-2xl ${scoreClass(score)}`}>
                {score}
            </strong>
            <span className="text-xs text-muted-foreground">{label}</span>
        </div>
    );
}

export function RecommendationBadge({ value }: { value: string }) {
    return (
        <Badge
            variant="outline"
            className={
                value.toLowerCase() === 'apply'
                    ? 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200'
                    : value.toLowerCase() === 'pass'
                      ? 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-900 dark:bg-rose-950 dark:text-rose-200'
                      : 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200'
            }
        >
            {value || 'Maybe'}
        </Badge>
    );
}
