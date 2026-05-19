# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## The three-repo picture

This repo is one of three siblings under `/home/adm1n/projects/univer/`. Understanding the relationship between them is the most important thing — touching shared tables or response shapes here breaks the others.

| Path | Stack | Role |
|---|---|---|
| `../univer/` | Yii2 (PHP 7.2 era, advanced template) | **Legacy source-of-truth**, still in production. Owns the schema for `e_*` tables. Has full domain coverage, OAuth2 server, 5-group API (Backend / Public / Student / Tutor / Fast). Use as the reference when behaviour is unclear. |
| `./` (univer-back) | Laravel 12 + PHP 8.4 (composer) / 8.3 (Docker, CI) | **New backend**, parallel to Yii2. Shares the same PostgreSQL DB. Adds 3 new tables (`e_password_reset_tokens`, `e_auth_refresh_tokens`, `e_system_login`) plus 5 hybrid columns (`guard_name`, `spatie_enabled`, `permission_name`). |
| `../univer-front/` | React 19 + Vite 7 + TS 5.9 + Zustand + react-query 5 + Tailwind 4 + shadcn-ui | **Sole frontend**. Calls this backend via Axios; relies on the `{success, data, message}` envelope and the auto-unwrapping interceptor in `src/lib/api/client.ts`. |

**Cross-cutting invariants** — any one of these can silently break the other repos if violated:

1. **`e_*` tables are jointly owned with Yii2.** Adding columns is acceptable only if both stacks tolerate them; renaming/dropping anything that the Yii2 models reference (`../univer/common/models/.../*.php`) will break production. Verify in the Yii2 model before changing schema.
2. **API response envelope is contractual:** every `api/*` JSON response must be `{success: bool, data?: any, message: string}` (with `errors` for 422, `retry_after` for 429). The frontend interceptor unwraps `data` and rejects when `success: false` — drift from this shape and the frontend will misread responses.
3. **JWT payload must carry `permissions` and user type metadata.** `univer-front/src/stores/auth/permissionStore.ts` decodes the JWT and uses the embedded permissions for UI gating. Don't drop those claims when refactoring auth.
4. **Yii2-format URL aliases in `routes/api.php` (`/api/v1/education/*`, `/api/v1/student/decree`, etc.) are still consumed** — by older mobile clients and possibly Yii2-side proxies. Treat them as a stable contract, not legacy debt to clean up.
5. **Permission strings are flat dotted resources** (`student.view`, `hemis.sync`, with `*` wildcard support like `teacher.*`). Same vocabulary is used by `CheckPermission` middleware here, by `RequirePermission` in the frontend, and by the Yii2 `e_admin_resource.path` rows. Don't invent a new format.

## Common commands

```bash
# Dev
php artisan serve                          # http://127.0.0.1:8000
php artisan migrate                        # run pending migrations
php artisan jwt:secret                     # rotate JWT signing key
php artisan docs:generate --all            # regenerate OpenAPI / Swagger
php artisan docs:generate --role=teacher   # docs scoped to one role

# Tests (read "Test database safety" below before running)
php artisan test
php artisan test --filter=AuthenticationTest               # single class
php artisan test --filter=test_employee_can_login          # single method
php artisan test --coverage --min=50                       # CI gate
php artisan test --stop-on-failure --testdox

# Quality (run before pushing — CI runs all three)
vendor/bin/phpstan analyse --memory-limit=2G               # larastan level 6, app/ only
vendor/bin/php-cs-fixer fix --dry-run --diff               # check
vendor/bin/php-cs-fixer fix                                # apply
composer audit                                             # advisories

# Docker / k8s shortcuts live in Makefile (`make help`)
make up                                    # docker-compose up -d
make artisan CMD="migrate"
make k8s-deploy                            # kubectl apply -k k8s/overlays/prod
```

## Test database safety

Tests are governed **only** by `USE_TEST_DATABASE` in `.env`. `phpunit.xml` and CLI flags cannot override it.

- `USE_TEST_DATABASE=true` + `DB_DATABASE_TEST=test_401` → `tests/CreatesApplication.php` swaps the pgsql connection to `test_401`, and `tests/SeedsTestData.php` allows `RefreshDatabase` + seeds `TestUsersSeeder`.
- `USE_TEST_DATABASE=false` (or unset) → tests run against `DB_DATABASE` (the live `hemis_401`) and `SeedsTestData::refreshDatabase()` is **hard-blocked**. Any attempt to wipe/seed throws.

When writing new tests, `use Tests\SeedsTestData;` instead of Laravel's bare `RefreshDatabase` — the trait wraps it with these guards. Adding `use RefreshDatabase` directly will be silently bypassed in production mode.

## Architecture

### Routing layout

Two route files are wired in `bootstrap/app.php`:

- `routes/api_v1.php` — mounted at `/api/v1`, canonical role-prefixed surface.
- `routes/api.php` — mounted at `/api`. Also hosts the **Yii2 compatibility layer** under `/api/v1/education/*` and `/api/v1/student/{decree,certificate,reference,...}` for the old univer-yii2 URLs. These delegate to the same V1 controllers (or `Compatibility\EducationController`).

When adding endpoints, prefer the canonical role-prefixed path in `api_v1.php`. Touch the compatibility block only when explicitly preserving a Yii2 URL.

### Role-scoped controllers

Controllers under `app/Http/Controllers/Api/V1/` are split by **role**, not by resource: `Admin/`, `Employee/`, `Teacher/`, `Student/`, `Compatibility/`. The same logical resource (subjects, attendance, grades) has separate controllers per role because each enforces different scopes/permissions. Don't merge them.

The frontend mirrors this split at `univer-front/src/modules/{admin,teacher,student,employee,shared}/`. When you add or rename a controller here, expect a corresponding module on the other side.

### Authentication & permission model

Three JWT guards (`config/auth.php`), all `tymon/jwt-auth`:

- `admin-api` / `employee-api` → provider `admins` → `App\Models\EAdmin` (staff: admin, teacher, rector, employee). `employee-api` is the preferred alias; `admin-api` is kept for legacy.
- `student-api` → provider `students` → `App\Models\EStudent`.
- `api` is a legacy alias for `admin-api`.

Authorization is **dual-mode**. Spatie permissions are installed, but the live system also reads the Yii2 path-based permission tables (`e_admin_resource.path`, `e_admin_role_resource`). The bridge is `app/Http/Middleware/CheckPermission.php` (alias `permission`), which queries `App\Services\Permission\PermissionCacheService` with a 10-minute Redis TTL. Format in routes is `resource.action` (e.g. `student.view`, `hemis.sync`); comma-separated lists are OR.

Permission switching at runtime: `POST /api/v1/employee/auth/role/switch` plus `GET /permissions` and `POST /permissions/check` — this is the *zero-trust* pattern. The frontend cannot lie about which permissions it has; the server re-checks every request. Don't undo this by trusting client-claimed permissions.

### Services / repositories / DTOs

Most domain logic lives in `app/Services/<Domain>/<Name>Service.php` (`Admin`, `Auth`, `Employee`, `OAuth`, `Permission`, `Menu`). Controllers should be thin and delegate. Repository and service **interfaces** exist only for `Menu` so far (`app/Contracts/Repositories`, `app/Contracts/Services`); other services are concrete classes resolved directly. Don't introduce interfaces gratuitously for one implementation — match the existing pattern.

`MultiTenantTranslationService` is registered as a singleton in `AppServiceProvider::register` and merges file-based base translations with per-university DB overrides. Resolve via `app(MultiTenantTranslationService::class)`.

### Models

Eloquent models in `app/Models/` follow the **Yii2 table prefix convention**, not Laravel's:

- `E*` → entity tables (`EStudent` → `e_student`, `EAdmin` → `e_admin`, `EAdminResource` → `e_admin_resource`).
- `H*` → handbook / reference tables (`HCountry`, `HLanguage`).
- `OAuth*` → OAuth2 server tables (compatible with `../univer/common/modules/oauth/`; do not rename).

When adding a new model that mirrors a Yii2 table, copy the table name from the Yii2 model's `tableName()` method — do not guess.

Cache invalidation is wired through observers registered in `AppServiceProvider::boot` (currently `EDepartment`, `ESubject`, `EGroup`). New cached resources need an observer here.

### Rate limiting

Custom limiters defined in `AppServiceProvider::configureRateLimiting()`:

| Limiter | Default | Configurable via |
|---|---|---|
| `api` | 60/min per user/IP | — |
| `auth` | 5/min per IP | `THROTTLE_AUTH_MAX_ATTEMPTS`, `THROTTLE_AUTH_DECAY_MINUTES` |
| `password` | 3 per 5min per IP | `THROTTLE_PASSWORD_MAX_ATTEMPTS`, `THROTTLE_PASSWORD_DECAY_MINUTES` |
| `students` | 30/min auth'd, 10/min anon | — |
| `public` | 100/min per IP | — |

Routes reference these via `throttle:<name>`. Tune via env vars rather than editing the provider.

### Production safeguards

`AppServiceProvider::blockDangerousQueries()` runs only in production and throws if `DROP TABLE / DROP DATABASE / TRUNCATE / ALTER TABLE / DROP COLUMN / DROP INDEX` reaches the connection. Schema changes must go through migrations — direct DDL via Tinker or seeders will be blocked at runtime in prod. This exists because Yii2 is the schema authority; ad-hoc DDL from the Laravel side has corrupted the schema before.

### API response shape (load-bearing)

The exception handlers in `bootstrap/app.php` enforce a consistent JSON envelope for any path matching `api/*`:

```json
{ "success": false, "message": "<localized message>", "error": "<short code>" }
```

Validation errors add an `errors` map; throttle responses add `retry_after`. New error paths must match this shape. Production strips DB error messages and stack traces; local/dev returns them. All exceptions are also reported to Sentry when `SENTRY_DSN` is set.

The frontend's Axios response interceptor (`univer-front/src/lib/api/client.ts:60–`) inspects `success` and unwraps `data` automatically. If you return a bare object (no `success` key), the frontend will pass it through as-is, which usually causes runtime type errors in TS. Always use Laravel's response helpers in this repo.

### Localization contract with frontend

Backend reads locale from (in order): `?l=` query param (Yii2-format codes like `uz-UZ`, `oz-UZ`, `ru-RU`, `en-US`), `X-Locale` header, or `Accept-Language` header. `App\Http\Middleware\SetLocale` normalizes them. The frontend's `languageStore` sets `X-Locale` and `Accept-Language` to short codes (`uz`, `oz`, `ru`, `en`). Both must be supported.

### OpenAPI / docs

Two doc stacks installed: `darkaonline/l5-swagger` and `dedoc/scramble`. Swagger autodiscovery is **disabled** in `composer.json` (`dont-discover`). A pre-built `api.json` (~692 KB) lives at the repo root; regenerate via `php artisan docs:generate --all` rather than editing it.

## Code style enforced by CI

- `.php-cs-fixer.php`: PSR-12, short array syntax, alpha-ordered imports, no unused imports, trailing comma in multilines, blank line before `return/throw/try/break/continue/declare`, one blank line between methods. Scope: `app/`, `config/`, `database/`, `routes/`, `tests/`.
- `phpstan.neon`: larastan **level 6**, scope `app/` only. `Http/Middleware`, `Console/Kernel.php`, `Exceptions/Handler.php` are excluded. Eloquent builder magic methods and common Laravel patterns are already ignored — don't add `@phpstan-ignore` for those.
- CI (`.github/workflows/ci.yml`) requires `--coverage --min=50` on `php artisan test`. PHPStan and CS Fixer steps use `continue-on-error: true` today, so failing them won't block merge, but they're still expected to be clean.

## Things that have surprised past contributors

- `database/migrations_backup/` and `database/migrations_backup_old/` are Yii2→Laravel migration history. **Do not run them.**
- Student auth lives at `/api/student/auth/login` (no `v1` prefix — `routes/api.php` is mounted at `/api`); employee auth lives at `/api/v1/employee/auth/login`. Yii2-compat aliases (`/api/v1/auth/student-login`) also exist.
- PHP 8.4 (composer.json) vs 8.3 (Dockerfile + CI matrix) is currently inconsistent. If you touch the build chain, pick one or ask.
- `e_*` migration changes need a corresponding edit-or-verify pass in the Yii2 model under `../univer/common/models/`. The Yii2 model defines the schema contract; the Laravel migration follows.
- The OAuth2 server endpoints (`/api/v1/oauth/*`) and OAuth model classes are designed to stay wire-compatible with the Yii2 OAuth2 module (`../univer/common/modules/oauth/`). External integrators expect Yii2-shaped responses; do not refactor without coordination.

## Project-scoped skills

Workflow skills for recurring backend tasks live in `.claude/skills/`:

- `add-role-endpoint` — add a permission-gated endpoint in the right `Api/V1/<Role>/` controller and wire it into both `api_v1.php` and the Yii2 compat layer if needed.
- `add-shared-table-migration` — write a migration that touches an `e_*` table, with the Yii2-cross-check protocol.
- `add-domain-service` — scaffold a `App\Services\<Domain>\<Name>Service.php` consistent with existing services.
