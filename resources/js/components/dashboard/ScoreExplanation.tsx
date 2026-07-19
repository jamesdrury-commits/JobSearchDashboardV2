import type { JobDetail } from '@/types/dashboard';
import { ScoreBadge } from './ScoreBadge';

type ScoreExplanationProps = {
    job: JobDetail;
};

export function ScoreExplanation({ job }: ScoreExplanationProps) {
    return (
        <section className="space-y-3">
            <h3 className="text-sm font-semibold">Score explanation</h3>
            <div className="grid grid-cols-3 gap-2">
                <ScoreBadge label="Priority" score={job.priority_score} />
                <ScoreBadge label="Career Fit" score={job.career_fit_score} />
                <ScoreBadge label="Life Fit" score={job.life_fit_score} />
            </div>
            <p className="rounded-md bg-muted/40 p-3 text-sm text-muted-foreground">
                {job.score_explanation}
            </p>
        </section>
    );
}
