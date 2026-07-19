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
            <Badge variant="outline">{status}</Badge>
            <Badge variant="outline">{applicationStatus || 'Not Started'}</Badge>
        </>
    );
}
