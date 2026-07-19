export type JobSummary = {
    id: number;
    company: string;
    role: string;
    url: string | null;
    salary: string;
    remote_status: string;
    match_score: number;
    priority_score: number;
    career_fit_score: number;
    life_fit_score: number;
    overall_recommendation: string;
    why_considering: string | null;
    tradeoffs_watch_outs: string | null;
    local_exception_reason: string | null;
    commute_notes: string | null;
    benefits_pension_notes: string | null;
    resume_angle: string | null;
    source_lane: string;
    executive_watch: boolean;
    status: string;
    last_seen: string | null;
    source: string;
    notes: string | null;
    generated_docs_summary: string | null;
    application_status: string;
    application_ready_at: string | null;
    approval_required: boolean;
    document_count: number;
    latest_operation: JobOperation | null;
};

export type PaginatedJobs = {
    data: JobSummary[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
};

export type RunStatus = {
    run_name: string;
    last_run_at: string;
    status: string;
    details: string | null;
};

export type DashboardFilters = {
    q: string;
    status: string;
    bucket: string;
    view: 'compact' | 'detailed' | string;
};

export type JobDocument = {
    id: number;
    document_type: string;
    display_filename: string;
    mime_type: string | null;
    size_bytes: number | null;
    created_at: string | null;
};

export type JobDetail = JobSummary & {
    description: string | null;
    score_explanation: string;
    documents: JobDocument[];
    generated_documents: JobDocument[];
    job_notes: Array<{
        id: number;
        body_markdown: string;
        source: string;
        created_at: string | null;
    }>;
    applications: Array<{
        id: number;
        status: string;
        applied_at: string | null;
        last_action_at: string | null;
        last_action: string | null;
        missing_fields: string[] | null;
        warnings: string[] | null;
        created_at: string | null;
        updated_at: string | null;
    }>;
    operations: JobOperation[];
    events: Array<{
        id: number;
        event_type: string;
        event_note: string | null;
        created_at: string | null;
    }>;
};

export type JobOperation = {
    id: number;
    operation_type: string;
    status: 'queued' | 'processing' | 'completed' | 'failed' | string;
    queued_at: string | null;
    started_at: string | null;
    finished_at: string | null;
    failure_reason?: string | null;
};
