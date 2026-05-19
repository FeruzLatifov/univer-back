---
name: add-role-endpoint
description: Add a new permission-gated endpoint to the Laravel backend that fits the role-scoped controller layout. Use when the user asks to add a route, endpoint, action, or feature for a specific role (admin, teacher, student, employee).
---

# Add a role-scoped, permission-gated endpoint

The backend splits controllers by **role** (`Api/V1/Admin`, `Api/V1/Teacher`, `Api/V1/Student`, `Api/V1/Employee`), not by resource. Every protected endpoint needs three things to be wired up correctly:

1. A controller method on the right role controller (or a new controller in the right role folder).
2. A route declaration in `routes/api_v1.php` (canonical) and **possibly** a Yii2-compat alias in `routes/api.php`.
3. A `permission:resource.action` middleware referencing a permission string that exists in `e_admin_resource` (the Yii2 source of truth for permissions).

## Decision flow

Ask yourself in order, do not skip:

1. **Which role(s) call this endpoint?** Admin only / Teacher only / Student only / Employee dashboard / multiple roles? The role decides the controller folder and the auth guard. Cross-role endpoints (messaging, forum, notifications) use the existing pattern in `routes/api.php` under `auth:student-api,admin-api` — they live under `app/Http/Controllers/Api/` (no V1/Role subfolder).
2. **Does this endpoint already exist in the Yii2 app (`../univer/api/controllers/`)?** If yes, two cases:
   - You're *mirroring* a Yii2 endpoint for the new frontend → use the canonical `/api/v1/<role>/...` path.
   - You're keeping a Yii2 URL alive for external clients → add it in the **compatibility block** of `routes/api.php` (lines ~540–648) pointing at the V1 controller.
3. **Does the permission string already exist?** Grep `e_admin_resource` (or check the Yii2 backend admin UI) for the dotted name like `student.view`. If not, ask the user whether to create it (separate skill: `add-permission-resource` on the Yii2 side, or seed it with `database/seeders/MapYii2ToLaravelPermissions`).

## File checklist

Open these files in order and make the matching edit:

1. **`app/Http/Controllers/Api/V1/<Role>/<Resource>Controller.php`**
   - Add the action method. Inject the service in the constructor (don't `app()->make` inside the method).
   - Use a `FormRequest` (under `app/Http/Requests/`) for any input validation; do not validate inline.
   - Return via Laravel's response helpers so the JSON envelope `{success, data, message}` is consistent. The frontend's axios interceptor (`../univer-front/src/lib/api/client.ts`) will reject the promise if `success: false`.
2. **`app/Services/<Domain>/<Name>Service.php`**
   - All business logic lives in the service. Controllers are thin pass-throughs.
   - If the service doesn't exist yet, run the `add-domain-service` skill first.
3. **`routes/api_v1.php`**
   - Add the route inside the matching `Route::middleware([...])->prefix('<role>')->group(function () { ... })` block.
   - Middleware stack: `'auth:<role>-api'`, `'throttle:api'` (or `'throttle:students'` for admin student CRUD), `'permission:<resource>.<action>'`.
4. **`routes/api.php`** *(only if preserving a Yii2 URL)*
   - Add the alias in the compatibility block, pointing at the same controller method.
5. **Tests** — `tests/Feature/Api/V1/<Role>/<Resource>Test.php`
   - `use Tests\SeedsTestData;` (NOT `RefreshDatabase` directly).
   - Cover: unauthenticated returns 401, missing permission returns 403, happy path returns 200 + expected envelope, validation errors return 422 with `errors` map.

## Verification

After implementation, verify these in order:

```bash
# Syntax / static
vendor/bin/phpstan analyse --memory-limit=2G app/Http/Controllers/Api/V1/<Role>/<Resource>Controller.php app/Services/<Domain>/<Name>Service.php
vendor/bin/php-cs-fixer fix --dry-run --diff app/Http/Controllers/Api/V1/<Role>/

# Tests
php artisan test --filter=<Resource>Test

# Smoke (with a token from /api/v1/<role>/auth/login)
curl -H "Authorization: Bearer <token>" http://127.0.0.1:8000/api/v1/<role>/<endpoint>

# Regenerate OpenAPI docs
php artisan docs:generate --role=<role>
```

## What NOT to do

- Don't put the logic in the controller. Even one-liners belong in the service if they can grow.
- Don't validate via `$request->validate([...])` inline — use a `FormRequest` class for testability and to surface errors in the standard 422 shape.
- Don't add a route under a new prefix; the role prefixes are stable (`student`, `v1/employee`, `v1/teacher`, plus the unprefixed admin routes at line 293 of `routes/api.php`).
- Don't bypass `permission:` middleware "because the controller checks it" — the middleware caches in Redis and is observable.
- Don't return a raw payload (no `success` key). The frontend interceptor will pass it through but Type definitions will mismatch.
