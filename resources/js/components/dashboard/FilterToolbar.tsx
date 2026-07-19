import { Search } from 'lucide-react';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';

const workflowBuckets = [
    { label: 'All', value: 'all' },
    { label: 'Queue', value: 'queue' },
    { label: 'Interested', value: 'interested' },
    { label: 'Applied', value: 'applied' },
    { label: 'Review', value: 'review' },
];

type FilterToolbarProps = {
    query: string;
    status: string;
    bucket: string;
    view: string;
    workflowStatuses: string[];
    onQueryChange: (value: string) => void;
    onStatusChange: (value: string) => void;
    onBucketChange: (value: string) => void;
    onViewChange: (value: string) => void;
};

export function FilterToolbar({
    query,
    status,
    bucket,
    view,
    workflowStatuses,
    onQueryChange,
    onStatusChange,
    onBucketChange,
    onViewChange,
}: FilterToolbarProps) {
    return (
        <section className="grid gap-3 rounded-md border bg-muted/30 p-3 lg:grid-cols-[1fr_auto_auto_auto]">
            <div className="relative">
                <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    value={query}
                    onChange={(event) => onQueryChange(event.target.value)}
                    className="pl-9"
                    placeholder="Search company, role, notes, salary, source"
                />
            </div>
            <Select value={status} onValueChange={onStatusChange}>
                <SelectTrigger className="w-full lg:w-[220px]">
                    <SelectValue placeholder="All statuses" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All statuses</SelectItem>
                    {workflowStatuses.map((item) => (
                        <SelectItem key={item} value={item}>
                            {item}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
            <Select value={bucket} onValueChange={onBucketChange}>
                <SelectTrigger className="w-full lg:w-[160px]">
                    <SelectValue placeholder="Workflow" />
                </SelectTrigger>
                <SelectContent>
                    {workflowBuckets.map((item) => (
                        <SelectItem key={item.value} value={item.value}>
                            {item.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
            <ToggleGroup
                type="single"
                value={view}
                onValueChange={(value) => value && onViewChange(value)}
                className="justify-start lg:justify-end"
            >
                <ToggleGroupItem value="compact" aria-label="Compact view">
                    Compact
                </ToggleGroupItem>
                <ToggleGroupItem value="detailed" aria-label="Detailed view">
                    Detailed
                </ToggleGroupItem>
            </ToggleGroup>
        </section>
    );
}
