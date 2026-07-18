# Job Search Dashboard V2

Laravel 13 / Inertia / React / TypeScript rewrite of the production V1 Job Search Assistant dashboard.

V1 stays untouched at `C:\Users\JamesDrury\Documents\Codex\ResumeJobSearch`.

## Development

```bash
docker compose build
docker compose run --rm jobsearch-v2-app composer install
docker compose run --rm jobsearch-v2-node pnpm run build
docker compose run --rm jobsearch-v2-app php artisan migrate
docker compose up -d jobsearch-v2-web jobsearch-v2-app
```

Open `http://127.0.0.1:8081`.

## Verification

```bash
docker compose run --rm jobsearch-v2-app ./vendor/bin/pint --test
docker compose run --rm jobsearch-v2-node pnpm run lint:check
docker compose run --rm jobsearch-v2-node pnpm run types:check
docker compose run --rm jobsearch-v2-node pnpm run build
docker compose run --rm -e APP_ENV=testing -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: -e DB_URL= jobsearch-v2-app php artisan test
```

## Database

Use `job_dashboard_v2` only. Do not point V2 at `job_dashboard`.

Create the database and users from `database/sql/create_v2_database.sql.example`, replacing placeholders with the generated values in the local `.env`.

## Import

Dry run:

```bash
docker compose run --rm jobsearch-v2-app php artisan jobsearch:import-v1 --dry-run
```

Database import:

```bash
docker compose run --rm jobsearch-v2-app php artisan jobsearch:import-v1
```

Document copy import:

```bash
docker compose run --rm jobsearch-v2-app php artisan jobsearch:import-v1 --copy-files
```

See `docs/migration-plan.md` for the full architecture, migration, rollback and cutover plan.
