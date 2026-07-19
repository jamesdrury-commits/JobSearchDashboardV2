import { Badge } from '@/components/ui/badge';

type WorkflowStatusProps = {
    status: string;
    applicationStatus: string;
};

export function WorkflowStatus({
    status,
    applicationStatus,
}: WorkflowStatusProps) {
    return (
        <>
            <Badge
                variant="outline"
                title="Current workflow status. Change it from the More Actions menu."
                className="cursor-default border-slate-200 bg-slate-50 text-slate-700 select-none dark:border-slate-800 dark:bg-slate-950 dark:text-slate-300"
            >
                Workflow: {status}
            </Badge>
            <Badge
                variant="outline"
                title="Current application/package state. Change it from the More Actions menu."
                className="cursor-default border-slate-200 bg-slate-50 text-slate-700 select-none dark:border-slate-800 dark:bg-slate-950 dark:text-slate-300"
            >
                Application: {applicationStatus || 'Not Started'}
            </Badge>
        </>
    );
}
