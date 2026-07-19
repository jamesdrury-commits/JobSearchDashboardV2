import type { JobDetail } from '@/types/dashboard';

type ApplicationTimelineProps = {
    job: JobDetail;
};

export function ApplicationTimeline({ job }: ApplicationTimelineProps) {
    const activities = [
        ...job.applications.map((application) => ({
            id: `application-${application.id}`,
            label: application.status,
            note: application.last_action,
            at: application.last_action_at ?? application.updated_at,
        })),
        ...job.events.map((event) => ({
            id: `event-${event.id}`,
            label: event.event_type.replaceAll('_', ' '),
            note: event.event_note,
            at: event.created_at,
        })),
    ].sort((a, b) => String(b.at ?? '').localeCompare(String(a.at ?? '')));

    return (
        <section className="space-y-3">
            <h3 className="text-sm font-semibold">Application activity</h3>
            {activities.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    No application activity has been recorded yet.
                </p>
            ) : (
                <div className="space-y-3">
                    {activities.slice(0, 12).map((activity) => (
                        <div
                            key={activity.id}
                            className="border-l-2 border-muted pl-3"
                        >
                            <p className="text-sm font-medium capitalize">
                                {activity.label}
                            </p>
                            {activity.note && (
                                <p className="text-sm text-muted-foreground">
                                    {activity.note}
                                </p>
                            )}
                            {activity.at && (
                                <p className="text-xs text-muted-foreground">
                                    {new Date(activity.at).toLocaleString()}
                                </p>
                            )}
                        </div>
                    ))}
                </div>
            )}
        </section>
    );
}
