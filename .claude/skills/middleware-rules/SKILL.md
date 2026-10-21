---
name: middleware-rules
description: Mandatory standards for HTTP middleware in app/Middleware and modules/*/Middleware and their registration (bootstrap/app.php, routes/api.php, http/<Area>/<Module>/routes.php). Use when creating or editing middleware, rate limiters, or attaching middleware to routes.
---

# Middleware

Cross-cutting middleware in `app/Middleware/` (`App\Middleware`), domain middleware in `modules/<Module>/Middleware/` (`Modules\<Module>\Middleware`, only `User` now). Plain classes, no base class (exception: `HandleCorsMiddleware extends Illuminate\Http\Middleware\HandleCors`). Canonical: `modules/User/Middleware/UserSuperadminMiddleware.php`, `app/Middleware/ConcurrentWritesPreventionMiddleware.php`.

## Existing middleware
- `App\Middleware\LocaleMiddleware` — whole API; `app()->setLocale()` from `Accept-Language` if it is in `singletons()->system->languages->active`, else the main language.
- `App\Middleware\ConcurrentWritesPreventionMiddleware` — whole API; POST/PUT/PATCH/DELETE take `Cache::lock("http_write_lock:$id:$path", 60)`, `abort(409, 'Too Many Attempts.')` if busy; skipped in `local`/`testing`.
- `App\Middleware\IdempotencyMiddleware` — `admin`, `company`; POST with `Idempotency-Key`: caches a successful `JsonResponse` for 3600 s under `http_idempotency:$userId:$path:md5(key)`, replays it with header `Idempotent-Replayed: true`.
- `Modules\User\Middleware\RoleMiddleware` — `admin`, `company`; `RoleHelper::canAccessRoute(user: user(), route: request()->route()->getName())`, else 403. The full route name is the permission.
- `Modules\User\Middleware\UserSuperadminMiddleware` — `http/Admin/History`, `http/Admin/System`, `http/Admin/User` groups and the `update-credential` route in `http/Admin/Company/routes.php`; `user()->getService()->isSuperadmin()` (user id 1), else 403.
- `App\Middleware\MaintenanceModeMiddleware` — not attached anywhere; throws `\Exception('Ведутся технические работы...')` (rendered as 400) to close a section temporarily.
- `App\Middleware\HandleCorsMiddleware` — not attached (body commented out); global CORS is the framework `HandleCors` prepended in `bootstrap/app.php`.

## Rules
- Name `<Purpose>Middleware`. One public method `handle(Request $request, \Closure $next): Response` (`Illuminate\Http\Request`, `Symfony\Component\HttpFoundation\Response`).
- Guard clause first, then `return $next($request);` (`UserSuperadminMiddleware`); ternary for a single check (`RoleMiddleware`); early `return $next($request)` for requests the middleware doesn't handle (`IdempotencyMiddleware`, `ConcurrentWritesPreventionMiddleware`).
- Deny with `abort(<code>, __('<Russian message>'))`: 403 access, 409 concurrent write. `bootstrap/app.php` renders every exception as JSON `{message}` with `getStatusCode()` or 400 — don't build error responses. Only `IdempotencyMiddleware` returns `response()->json(...)` (cached replay).
- Current user via `user()`. In the global stack (also public routes) use null-safe `user()?->id ?? $request->ip()` (`ConcurrentWritesPreventionMiddleware`); `user()->id` only behind `auth:sanctum` (`IdempotencyMiddleware`).
- Permission logic lives in Helper/Service (`RoleHelper::canAccessRoute()`, `UserService::isSuperadmin()`), not in middleware.
- Environment skip: `in_array(app()->environment(), ['local', 'testing'])`.
- Cache keys `http_<purpose>:<userId|ip>:<path>[:<extra>]`; locks: `Cache::lock($key, 60)` + `try { return $next($request); } finally { $lock->release(); }`.
- Register by class reference; strings only for framework middleware/groups: `'api'`, `'auth:sanctum'`, `'throttle:<limiter|N>'`. No aliases in `bootstrap/app.php`.
  - global: `$middleware->prepend(X::class)` in `bootstrap/app.php` `withMiddleware()` (only `HandleCors`);
  - whole API / area: arrays in `routes/api.php` — stack `[LocaleMiddleware::class, 'api', 'throttle:api', ConcurrentWritesPreventionMiddleware::class]`; `admin`/`company` add `['auth:sanctum', RoleMiddleware::class, IdempotencyMiddleware::class]`; `common` adds nothing; `emulator` only registered when `!app()->isProduction()`;
  - module group: `Route::prefix('history')->middleware(X::class)->group(...)` (`http/Admin/History/routes.php`);
  - nested group: `Route::middleware(X::class)->group(...)` (`http/Admin/User/routes.php`);
  - single route: `Route::middleware(X::class)->patch(...)->name(...)` (`http/Admin/Company/routes.php`) or `->middleware([...])` after the route (`logout` in `http/Common/Auth/routes.php`).
- `common/*` routes that need a user add `->middleware(['auth:sanctum'])` in their own `routes.php` (`http/Common/User`, `Record`, `System`, `Auth` logout). `RoleMiddleware` is not used there (`common.*` is in `RoleHelper::$excludedRoutes`).
- Superadmin-only route names are also in `RoleHelper::$excludedRoutes` (`admin.history.*`, `admin.system.*`, `admin.user.*`, `admin.company.company.update-credential`): role check is skipped, `UserSuperadminMiddleware` replaces it — update both together.
- Rate limits: named limiters in `bootstrap/app.php` `->booting()` via `RateLimiter::for()` (`api`: 300/min production, 1000/min otherwise, per user or IP; `auth`: per IP+path 5/min login, 3/min others, plus 10/h per username), attached as `->middleware('throttle:auth')` (`http/Common/Auth/routes.php`); one-off `->middleware('throttle:3')` (`http/Common/Record/routes.php`).
- Closure middleware exists only in `ResourceController::__construct()` (binds Search/FormRequest per request) — framework plumbing, not a pattern for new code.

## Template
```php
<?php

namespace Modules\<Module>\Middleware;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class <Purpose>Middleware
{
    public function handle(Request $request, \Closure $next): Response
    {
        if (!user()->getService()-><check>()) {
            abort(403, __('<Сообщение об ошибке>'));
        }

        return $next($request);
    }
}
```

Route usage (`http/<Area>/<Module>/routes.php`):
```php
Route::prefix('<prefix>')
    ->middleware(<Purpose>Middleware::class)
    ->group(function () {
        // ...
    });
```

## Don't
- Don't register middleware aliases or `$middleware->group()` in `bootstrap/app.php` — reference the class.
- Don't add constructors, `terminate()` or a base class.
- Don't put queries or role resolution in middleware — call a Helper/Service.
- Don't return `response()->json(...)` for errors — `abort()`.
- Don't put module-specific middleware in `app/Middleware/`.
- Don't write middleware for throttling — named `RateLimiter` + `throttle:`.
- Don't attach `RoleMiddleware` or `IdempotencyMiddleware` in module `routes.php` — they come from `routes/api.php`.
