# Job Search Dashboard V2 Cutover Checklist

Do not start cutover until V1 is backed up, V2 migrations are current, and the cross-user isolation tests pass.

## Pre-Cutover

- Confirm V1 `ResumeJobSearch` project and `job_dashboard` database are untouched.
- Back up `job_dashboard_v2`.
- Run `php artisan migrate --force` in the V2 app container.
- Run `php artisan test`.
- Run `pnpm run lint:check`.
- Run `pnpm run types:check`.
- Run `pnpm run build`.
- Verify `/register` remains disabled.
- Verify only the intended owner account can see imported jobs.
- Verify User B cannot list, search, view, modify, delete, download, or generate documents for User A data.
- Verify Top 20 ordering matches the import baseline.
- Verify dashboard job cards include direct `Open Posting` links where source URLs exist.
- Verify generated document downloads use authorized controller routes and friendly filenames.

## Local Acceptance

- Open [V2 dashboard](http://127.0.0.1:8081/dashboard).
- Confirm the Top 20 dashboard appears near the top.
- Confirm search, filters, pagination, compact view, and detailed view work.
- Open a job drawer and confirm notes, score explanation, documents, activity, and full description load on demand.
- Request a package and confirm queue state appears as queued, processing, completed, or failed.

## Public Exposure Hold

- Do not switch Cloudflare Tunnel.
- Do not add router port forwarding.
- Do not expose V2 publicly until production host, proxy, HTTPS, cookie, and hostname settings are explicitly reviewed.

## Rollback

- Stop V2 containers with `docker compose stop`.
- Leave V1 running unchanged.
- Restore `job_dashboard_v2` only if a V2 migration or queued task caused V2-only data damage.
- Do not restore or modify the V1 database as part of a V2 rollback.
