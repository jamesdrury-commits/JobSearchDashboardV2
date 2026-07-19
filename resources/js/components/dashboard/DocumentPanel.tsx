import { Download, FileText } from 'lucide-react';
import { Button } from '@/components/ui/button';
import type { JobDetail, JobDocument } from '@/types/dashboard';

type DocumentPanelProps = {
    job: JobDetail;
};

export function DocumentPanel({ job }: DocumentPanelProps) {
    const documents: JobDocument[] = [
        ...job.documents,
        ...job.generated_documents,
    ];

    return (
        <section className="space-y-3">
            <h3 className="text-sm font-semibold">Documents</h3>
            {documents.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    No generated documents are attached yet.
                </p>
            ) : (
                <div className="space-y-2">
                    {documents.map((document) => (
                        <div
                            key={`${document.document_type}-${document.id}`}
                            className="flex items-start justify-between gap-3 rounded-md border p-3"
                        >
                            <div className="flex min-w-0 items-start gap-3">
                                <FileText className="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                                <div className="min-w-0">
                                    <p className="truncate text-sm font-medium">
                                        {document.display_filename}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {document.document_type.replaceAll(
                                            '_',
                                            ' ',
                                        )}
                                        {document.size_bytes
                                            ? ` - ${Math.ceil(document.size_bytes / 1024)} KB`
                                            : ''}
                                    </p>
                                </div>
                            </div>
                            {document.download_url && (
                                <Button asChild size="sm" variant="outline">
                                    <a href={document.download_url}>
                                        <Download />
                                        Download
                                    </a>
                                </Button>
                            )}
                        </div>
                    ))}
                </div>
            )}
            {job.generated_docs_summary && (
                <p className="rounded-md bg-muted/40 p-3 text-sm text-muted-foreground">
                    {job.generated_docs_summary}
                </p>
            )}
        </section>
    );
}
