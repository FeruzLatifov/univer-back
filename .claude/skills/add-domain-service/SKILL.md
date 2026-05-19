---
name: add-domain-service
description: Scaffold a new service class under app/Services/<Domain>/ in the style used across the codebase. Use when the user asks to add business logic for a new resource or extract logic out of a controller.
---

# Add a domain service

Controllers in this app are thin pass-throughs. All business logic lives in `app/Services/<Domain>/<Name>Service.php`. The service classes are **concrete** (not interface-based) — interfaces only exist for `Menu` because it has a real abstraction need. Don't introduce an interface for a single implementation; match the existing pattern.

## Decision flow

1. **What domain does this service belong to?** Look at existing folders:
   - `Admin/` — for admin panel operations on students, employees, groups, departments, specialties, HEMIS sync.
   - `Auth/` — JWT refresh tokens, login flows.
   - `Employee/` — teacher/employee features (dashboard, documents, menu, teacher load).
   - `OAuth/` — OAuth2 server.
   - `Permission/` — `PermissionCacheService` and friends.
   - `Menu/` — menu rendering.
   - Top-level `app/Services/` — cross-cutting (`CacheService`, `ExportService`, `NotificationService`, `LanguageMapper`, etc.).
   - If no domain matches, ask the user — don't invent a new domain unless they want one.

2. **Is this service primarily a thin Eloquent wrapper?** If yes, reconsider — you may not need a service at all. The threshold is: more than one Eloquent call, conditional logic, or coordination across models → service. Single `Model::find($id)->update($data)` → controller can call the model directly.

3. **Does it need shared dependencies?** Constructor inject them. Common ones: `App\Services\CacheService`, `App\Services\Permission\PermissionCacheService`, `Illuminate\Support\Facades\DB`. Resolve via type-hinted constructor, not `app()` calls inside methods.

## File checklist

1. **`app/Services/<Domain>/<Name>Service.php`**
   - Namespace: `App\Services\<Domain>`.
   - Constructor injection for dependencies; assign to typed private properties.
   - Public methods named for the use case (`createStudent`, `enrollInSubject`, `syncWithHemis`), not for the implementation (`insertStudentRow`).
   - Throw domain exceptions (or Laravel's HTTP exceptions) for failure modes; don't return null/false sentinel values. The controller's job is to translate exceptions to API responses.
   - Wrap any multi-step write in `DB::transaction(fn () => ...)`.
2. **No service-provider binding needed** — Laravel resolves the class via auto-injection. Don't add a singleton binding in `AppServiceProvider` unless the service genuinely needs to be shared (e.g. `MultiTenantTranslationService`, which caches in-memory across requests).
3. **`tests/Unit/Services/<Domain>/<Name>ServiceTest.php`**
   - Pest test class extending `TestCase`.
   - `use Tests\SeedsTestData;` (NOT `RefreshDatabase` directly).
   - Test the service in isolation; mock external HTTP / queue calls; let Eloquent talk to the test DB.

## Style guide (match existing services)

- **Method visibility**: public for the use-case API, private/protected for internals. Don't expose helpers.
- **Return types**: always declared. Use `Collection<int, Model>` rather than `array` where you can.
- **No static methods** unless they're true factories (no I/O, no state).
- **Logging**: `Log::info`/`Log::warning` for notable events (HEMIS sync results, permission denials). Don't log on every method entry.
- **Cache invalidation**: if the service writes to a model that has an observer in `AppServiceProvider::boot` (`EDepartment`, `ESubject`, `EGroup`), the observer handles it — don't double-invalidate.

## Verification

```bash
# Static analysis
vendor/bin/phpstan analyse --memory-limit=2G app/Services/<Domain>/<Name>Service.php

# Style
vendor/bin/php-cs-fixer fix app/Services/<Domain>/<Name>Service.php

# Tests
php artisan test --filter=<Name>ServiceTest
```

## What NOT to do

- **Don't** introduce an interface (`<Name>ServiceInterface`) for a single implementation. The codebase only has interfaces for `Menu` and that was for a genuine abstraction reason.
- **Don't** call `request()` or any HTTP-context helpers from inside a service. Pass the input as parameters; the controller is responsible for HTTP-to-domain translation.
- **Don't** add a facade for the service. Use dependency injection.
- **Don't** put DB transactions in the controller. The service owns its transaction boundaries.
- **Don't** swallow exceptions for "robustness". Let them bubble; let the renderer in `bootstrap/app.php` convert them to JSON.
