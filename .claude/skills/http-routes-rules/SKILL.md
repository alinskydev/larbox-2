---
name: http-routes-rules
description: Mandatory standards for http/<Area>/<Module>/routes.php and the area groups in routes/api.php (prefixes, route names = role permissions, custom apiResource from App\Routing\Router / ResourceRegistrar, middleware, Admin/Company/Common/Emulator areas). Use when creating or editing any routes.php or adding an endpoint.
---

# HTTP Routes Rules

Location: `http/<Area>/<Module>/routes.php` (no namespace), auto-loaded by `routes/api.php` via `glob(base_path('http/<Area>/*/routes.php'))`; no `/api` prefix (`bootstrap/app.php`). Router: `App\Routing\Router` + `App\Routing\ResourceRegistrar`, bound in `App\Base\Application`.
Canonical: `http/Company/Trade/routes.php`, `http/Company/Product/routes.php`, `http/Common/User/routes.php`.

## Areas (`routes/api.php`)
All groups run `LocaleMiddleware`, `api`, `throttle:api`, `ConcurrentWritesPreventionMiddleware`.
- `admin/`, `company/` — `auth:sanctum` + `RoleMiddleware` + `IdempotencyMiddleware`. The full route name is the permission; `RoleHelper::canAccessRoute()` also requires the user type to match the prefix (`admin.` → admin, `company.` → director/employee) and, for employees, the director to own the route too.
- `common/` — no auth by default; files add `->middleware(['auth:sanctum'])` themselves (`Common/User`, `Common/Record`, `Common/System`, `auth/logout`).
- `emulator/` — registered only when `!app()->isProduction()`.

## Rules
- File = alphabetical `use` imports + one root `Route::prefix('<module-kebab>')->group(function () { ... });`, with `->middleware()` / `->group()` on new lines. Prefix mirrors the module (`trade`, `product`, `user`); the entity segment repeats even when equal (`company/stock/stock`, `admin/user/user`); multi-word entities are kebab (`purchase-product`, `store-http-log`). Only exception: `Common/System/routes.php` registers a single `get('system', ...)`.
- Names: only a short local `->name('<kebab>')`; `RoleHelper::fullRouteNames()` (called in `AppServiceProvider::boot`) prepends the URL prefix with `/` → `.`: `company.trade.purchase.add-products`, `admin.system.setting.update`, `company.stock.stock.index`.
- Every route is named — names feed `RoleMiddleware` and the role editor tree (`RoleHelper::routeTree()`).
- CRUD: `Route::apiResource('<entity>', <Entity>Controller::class)` registers `index` GET, `create` POST, `show` GET `{id}`, `update` PUT/PATCH `{model}`, `delete` DELETE `{model}`, `restore` DELETE `{id}/restore`. `deleteMultiple`/`restoreMultiple` are not registered (commented out in `Router::apiResource`). `id`, `model`, `index` params are digits-only (`bootstrap/app.php`).
- Full resource only for soft-delete models; otherwise `->except(['restore'])` (`Role`, `Subscription`, `Option`) — `ResourceController::restore()` throws without `SoftDeletes`. Partial sets via `->only([...])`: `['index', 'show']`, `['index', 'show', 'create']`, `['index', 'show', 'create', 'update']` (`Company/Trade/routes.php`).
- Entity with extra endpoints: `Route::prefix('<entity>')->group(function () { <custom routes>; Route::apiResource('', <Entity>Controller::class)...; });` — custom routes first, `apiResource('')` last.
- Custom route: `Route::<verb>('<path>', [<Entity>Controller::class, '<camelMethod>'])->name('<path-kebab>');` (move `->name()` to the next line when long). Record actions use `'{model}/<action>'` (`patch('{model}/complete', ...)->name('complete')`); extra params by name (`'{model}/set-name/{value}'` → `set-name`); sub-features nest a prefix (`excel-import` → `example`, `process`).
- Verbs: GET reads/downloads (`tree`, `available-routes`, `{model}/download`, `excel-import/example`); POST creates/processes (`{model}/add-products`, `excel-import/process`, `login`, `send-code`); PATCH state changes and partial ops (`{model}/complete`, `{model}/cancel`, `{model}/return`, `{model}/remove-products`, `move`, `mark-all-seen`, `{model}/auth-as`); PUT full singleton update.
- Singletons (no id): `Route::prefix('setting')->group(...)` with `Route::get('', [C::class, 'index'])->name('index')` and `Route::put('', [C::class, 'update'])->name('update')` (`Admin/System/routes.php`, profile in `Common/User/routes.php`).
- Invokable controllers: `Route::get('dictionary', DictionaryController::class)->name('dictionary');`, `Route::get('{type}', IndexController::class)->name('index');`.
- Superadmin-only: `->middleware(UserSuperadminMiddleware::class)` on the module group (`Admin/History`, `Admin/System`), an inner group (`Admin/User`) or one route (`update-credential` in `Admin/Company`). All such routes are also listed in `RoleHelper::$excludedRoutes`, so they are not assignable permissions.
- Helper endpoints that must not be permissions also go to `RoleHelper::$excludedRoutes` (`company.user.role.available-routes`); `common.*` and `emulator.*` are excluded already.
- Throttling: `Common/Auth` group uses the named `throttle:auth` limiter (`bootstrap/app.php`: per path+IP 5/min for login, 3/min otherwise; per path+username 10/hour); public writes add `->middleware('throttle:3')` (`Common/Record` create).
- Own-records permission: inside the entity prefix call `RolePermissionHelper::registerAllRecordsRoute();` (adds `<entity>.permission-all-records`) and add `public const ROUTE_<AREA>_<MODULE>_<ENTITY> = '<area>.<module>.<entity>';` to `Modules\User\Helpers\Role\RolePermissionHelper`; the Filter calls `filterByOwnRecords()` (brand in `Company/Product/routes.php`).
- Emulator routes mirror the external API path, verb and casing (`post('sendMessage', ...)->name('sendMessage')`, `post('update/{id}', ...)`), still named.

## Template
```php
<?php

use Http\<Area>\<Module>\Controllers\<Entity>Controller;
use Http\<Area>\<Module>\Controllers\<Other>Controller;
use Illuminate\Support\Facades\Route;

Route::prefix('<module>')
    ->group(function () {
        Route::prefix('<entity>')
            ->group(function () {
                Route::post('{model}/add-items', [<Entity>Controller::class, 'addItems'])->name('add-items');
                Route::patch('{model}/complete', [<Entity>Controller::class, 'complete'])->name('complete');

                Route::apiResource('', <Entity>Controller::class)->only(['index', 'show', 'create', 'update']);
            });

        Route::apiResource('<other>', <Other>Controller::class)->only(['index', 'show']);
    });
```

## Don't
- Don't use `Route::resource`, `Route::controller` or closure handlers (only `registerAllRecordsRoute()` registers one); outside Emulator use `{model}` for record routes, not `{id}`.
- Don't leave routes unnamed, write full dotted names, or chain `->name()` twice — Laravel appends (`Common/Record/routes.php` create resolves to `createcreate`).
- Don't add `auth:sanctum` / `RoleMiddleware` / `IdempotencyMiddleware` in `admin`/`company` files — `routes/api.php` applies them.
- Don't register module routes outside `http/<Area>/<Module>/routes.php` or edit `routes/api.php` for a module.
- Don't declare custom routes after `apiResource('')` in the same group.
