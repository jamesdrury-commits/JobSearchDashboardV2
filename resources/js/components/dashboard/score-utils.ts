export function recommendationClass(value: string) {
    const normalized = value.toLowerCase();

    if (normalized === 'apply') {
        return 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200';
    }

    if (normalized === 'pass') {
        return 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-900 dark:bg-rose-950 dark:text-rose-200';
    }

    return 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200';
}

export function scoreClass(score: number) {
    if (score >= 80) {
        return 'text-emerald-700 dark:text-emerald-300';
    }

    if (score >= 55) {
        return 'text-amber-700 dark:text-amber-300';
    }

    return 'text-rose-700 dark:text-rose-300';
}

export function splitNotes(value: string | null) {
    return String(value ?? '')
        .split(/[;\n\r]+/)
        .map((part) => part.trim())
        .filter(Boolean);
}
