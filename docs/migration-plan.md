# Job Search Dashboard V2 Migration Plan

## Isolation Rules

- V1 production path remains `C:\Users\JamesDrury\Documents\Codex\ResumeJobSearch`.
- V2 path is `C:\Users\JamesDrury\Documents\Codex\JobSearchDashboardV2`.
- V2 database is `job_dashboard_v2`.
- V1 import source is read-only from `job_search_assistant`.
- V2 web binds only to `127.0.0.1:8081`.
- Docker names are prefixed with `jobsearch-v2`.
- Do not expose V2 through Cloudflare or router forwarding during development.

## Docker Architecture

- `jobsearch-v2-web`: nginx, bound to `127.0.0.1:8081`.
- `jobsearch-v2-app`: PHP 8.4 FPM, Laravel 13, Composer.
- `jobsearch-v2-node`: Node 24 build helper for React/Inertia assets.
- `jobsearch-v2-net`: private Docker network.
- `jobsearch-v2-vendor`, `jobsearch-v2-node-modules`, `jobsearch-v2-storage`: V2-only volumes.

MariaDB runs on the Synology NAS and is not exposed by this compose file.

## Migration Plan

1. Create `job_dashboard_v2` and V2-only users from `database/sql/create_v2_database.sql.example`.
2. Copy the generated local `.env` passwords into the SQL before running it on MariaDB.
3. Run `docker compose build`.
4. Run `docker compose run --rm jobsearch-v2-app composer install`.
5. Run `docker compose run --rm jobsearch-v2-node pnpm run build`.
6. Run `docker compose run --rm jobsearch-v2-app php artisan migrate`.
7. Run `docker compose run --rm jobsearch-v2-app php artisan jobsearch:import-v1 --dry-run`.
8. Run `docker compose run --rm jobsearch-v2-app php artisan jobsearch:import-v1`.
9. Set `V1_GENERATED_FILES_PATH` and rerun with `--copy-files` when document copying is desired.

## V1 Feature Inventory

- Career Fit, Life Fit, overall recommendation, ranking and score ordering.
- Job source, source lane, executive watch, remote/location, salary.
- Workflow statuses and application queue statuses.
- Notes, rationale, tradeoffs, local exceptions, commute, benefits, resume angle.
- Generated resume, cover letter, review summary, screenshots and application review references.
- Dashboard run status for sync, discovery and generation.
- Automation-compatible upsert, generated-document, application, run-status and file endpoints.

## Data Import Plan

The `jobsearch:import-v1` Artisan command reads V1 through the `v1_import` connection and writes only to the default V2 connection. It preserves V1 IDs in `v1_job_id` and `v1_event_id`, records import runs, and logs row-level errors without changing V1.

Generated files are not copied by default. With `--copy-files`, files under `V1_GENERATED_FILES_PATH` are copied into V2 private storage and linked through `generated_documents`.

## Rollback And Cutover

Rollback during development:

```bash
docker compose down
```

Then drop or truncate only `job_dashboard_v2` if needed. V1 keeps its path, database and public endpoint unchanged.

Cutover later requires explicit approval: final dry run, final import, count comparison, local browser verification, automation endpoint switch, and a fallback window with V1 left intact.
