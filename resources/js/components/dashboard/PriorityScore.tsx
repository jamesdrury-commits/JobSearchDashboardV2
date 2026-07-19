import { Gauge } from 'lucide-react';
import { scoreClass } from './score-utils';

type PriorityScoreProps = {
    score: number;
};

export function PriorityScore({ score }: PriorityScoreProps) {
    return (
        <div className="flex min-w-[92px] items-center gap-2 rounded-md border px-3 py-2">
            <Gauge className={`size-4 ${scoreClass(score)}`} />
            <div>
                <strong className={`block leading-none ${scoreClass(score)}`}>
                    {score}
                </strong>
                <span className="text-xs text-muted-foreground">Priority</span>
            </div>
        </div>
    );
}
