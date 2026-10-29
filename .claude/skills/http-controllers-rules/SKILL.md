---
name: http-controllers-rules
description: Mandatory standards for HTTP controllers in http/<Area>/<Module>/Controllers (resource, nested-set, plain and invokable controllers, controller traits) and their bases in app/Controllers, app/NestedSet and app/Helpers/Controller. Use when creating or editing any controller or controller action.
---

# HTTP Controllers Rules

Location: `http/<Area>/<Module>/Controllers/<Entity>Controller.php`, namespace `Http\<Area>\<Module>\Controllers`.
Bases: `App\Controllers\ResourceController` (CRUD), `App\NestedSet\NestedSetController` (tree entities, extends `ResourceController`), `App\Controllers\Controller` (everything else; `successResponse(int $status = 200)` → `{"message": "Success"}`, `successResponseWithAccessToken(User $user)`).
Canonical: `http/Company/Trade/Controllers/PurchaseController.php`, `http/Company/Stock/Controllers/StockController.php`, `http/Common/User/Controllers/ProfileController.php`.

## Rules
- CRUD entity → extend `ResourceController`; the constructor only calls `parent::__construct()` with named args `model: new X()`, `search: new XSearch()`, `filter: new XFilter()`, `formRequestClass: XRequest::class`. Model/Search come from `Modules\<Module>\Models|Search`, Filter/Request from the same HTTP module.
- Pass only what is used: read-only controllers omit `formRequestClass` (`Company/Trade/Controllers/SaleProductController.php`); `filter` is omitted only for unscoped admin data (`Admin/System/Controllers/LanguageController.php`, `Admin/User/Controllers/UserController.php`, `Admin/Company/Controllers/SubscriptionController.php`); `search` (defaults to a bare `new Search()`) omitted when there is no list (`Admin/User/Controllers/NotificationController.php`). `resourceClass:` defaults to `$model->resourceClass`; pass it only for a different Resource (`Common/User/Controllers/NotificationController.php`).
- Controller, Filter and Request are named after the endpoint entity, not the model: `Company/User/Controllers/EmployeeController.php` = `User` + `UserSearch` + `EmployeeFilter` + `EmployeeRequest`.
- The constructor's closure middleware builds `$this->search->query` from `$model->query()` + `with/filter/sort/show/page_size` + `Filter::process()`, and binds `ValidatesWhenResolved` to `formRequestClass`. `public Model $model` is read by `ResourceFormRequest` for `create`.
- Tree entities extend `NestedSetController` → inherits `tree()` and `move(NestedSetMoveRequest $request)` (`Company/Product/Controllers/CategoryController.php`). No AdjacencyList controller exists.
- Inherited actions: `index()`, `show(int $id)`, `create(ValidatesWhenResolved $request)`, `update(ValidatesWhenResolved $request)`, `delete(Model $model)`, `restore(int $id)`; `deleteMultiple()`/`restoreMultiple()` exist but are not routed. Never reimplement them; override only to extend, calling `parent::`.
- Overridden `create`/`update` keep `ValidatesWhenResolved $request` and use `$request->model` / `$request->validatedData` (`Admin/User/Controllers/UserController::update`, Admin `NotificationController::create`).
- Overridden `show` keeps `show(int $id)` and calls `parent::show($id)` or reads `$this->search->query->findOrFail($id)` (Common `NotificationController::show`, `Company/Product/Controllers/ProductController::show`).
- `$this->search->query` already has Search + Filter applied — use it in custom list actions; `->clone()` before mutating it for a side query (Common `NotificationController::show`, `markAllSeen`).
- `{model}` binding: one global `Route::bind('model')` in `AppServiceProvider::boot` calls `ResourceController::resolveRouteModel($value)` = `$model->query()` + `Filter::process()` (when set) + `firstOrFail()`; other controllers get the raw value and implicit binding. Record actions type-hint the concrete model (`complete(Purchase $model)`, `authAs(User $model)`); foreign records are unreachable. The bound model has no Search `with[]`.
- `Model::preventLazyLoading()` is on in `local`/`testing`: custom queries eager-load what the Resource/accessors touch (`->with([...])` in `Common/Record/Controllers/IndexController.php`), single models use `loadMissing([...])` (`ProfileController::index`); call `$model->unsetRelations()` before returning a saved model (`ResourceController::create/update`, `CreateController`).
- Actions with input type-hint a concrete request (`addProducts(PurchaseAddProductsRequest $request)`), read `$request->validatedData[...]` into variables, then delegate domain work to the model service: `$request->model->getService()->addProducts($products)` (`PurchaseController`, `SaleController::return`) or `$request->model->safelySave($request->validatedData)` (`Admin/Company/Controllers/CompanyController::updateCredential`).
- Status switches: `$model->safelySave(['status' => XStatusEnum::Y->value])` (`PurchaseController::complete`). One validated field: `ControllerModelHelper::setValues(model:, values:, rules:, condition:, conditionErrorMessage:)` (`BrandController::setName`); same helper has `toggleValue()`, `delete()`, `checkCondition()` (failed condition → `\Exception` → 400).
- Persist only via `safelySave()` / `safelyDelete()` / `safelyRestore()` / `safelyDBProcess()`; locks via `DatabaseTransactionHelper::lock()` inside `safelyDBProcess()` (`NestedSetController::move`). Bulk updates on the filtered query use `->getQuery()->update([...])` (Common `NotificationController`); service-row cleanup by query `->delete()` (Admin `CompanyController::resetDailyLimit`).
- Heavy work → `dispatch(new XJob(...))` + `$this->successResponse()` (Admin `NotificationController::create`, `ProductControllerExcelImportTrait::excelImportProcess`).
- Totals on `index`: `$response = parent::index(); $data = $response->getData(true); $data['summary'] = $this->search->query->cloneForSummary()->select(...)->getQuery()->first(); return $response->setData($data);` (`StockController::index`, Product `ProductController::index`).
- Alternate formats via `?format=`: `excel` in `index` → `return ExcelExportHelper::process(jobClass:, query: $this->search->query)` (sync file or 202 after dispatch); `pdf` in `show` → `PdfHelper::download(path:, name:, params:)` (`Company/Product/Controllers/ProductController.php`).
- Responses: `response()->json($data)` with the payload prepared in a variable; `$this->successResponse()` when no payload; `$this->successResponseWithAccessToken($user)` after login/password changes (`AuthController::login`, `EmployeeController::update`, `ProfileController::update`, `ResetPasswordController::complete`); files via `ControllerStreamHelper::download(name:, path:|content:, shouldBeDeleted:)` (`NotificationController::download`) or `ExcelImportHelper::downloadExample($path)`. Single resource: `$model->resourceClass::make($model)`.
- Every public action declares a return type: `JsonResponse`, `StreamedResponse`, `JsonResponse|StreamedResponse` (`Symfony\Component\HttpFoundation\Response` in `NestedSetController`, `ResetPasswordController`).
- Errors (no `abort()` in `http/`): business errors `throw new <Entity>Exception(<Entity>Exception::CONST)` (`UserController::authAs`, `NotificationController::download`; see exceptions-rules). Hide an inaccessible endpoint/type with `throw new NotFoundHttpException()` (404, `Common/Record/Controllers/IndexController.php`, `CreateController.php`); hide an unsupported record with `throw new ModelNotFoundException()` (400 «Запись не найдена», `NotificationController::download`).
- Many extra actions → trait `Controllers/Traits/<Entity>/<Entity>Controller<Feature>Trait.php` with `@mixin Controller`, `use`d in the controller (`ProductControllerExcelImportTrait`).
- Single-endpoint handlers are invokable `Controller`s with `__invoke()` and `private` helpers (`Admin/History/Controllers/DictionaryController.php`, `Common/System/Controllers/SystemController.php`).
- Non-CRUD endpoints (auth, profile, settings) extend `Controller` and type-hint concrete requests; current user via `user()` (`Admin/System/Controllers/SettingController.php`, `Common/Auth/Controllers/ResetPasswordController.php`).
- `common/record/{type}` dropdowns: add a `match ($type)` arm with `RecordIndexParamsDTO(query:, field:, additionalFields:, isAccessible:)` / `RecordCreateParamsDTO(model:, nonLocalizedRules:, localizedRules:, isAccessible:)`, `default => throw new NotFoundHttpException()`; there is no Filter there, scope the query with `filterBy*()` yourself.
- Areas: **Admin** — back-office, scoping by administered companies; **Company** — tenant endpoints, every resource controller has a Filter; **Common** — auth, profile, notifications, record lists, plain or invokable controllers; **Emulator** — fakes of Storage/Store/Telegram APIs: plain `Controller`, hardcoded responses, no DB, method names mirror the external API (`Emulator/Telegram/Controllers/TelegramController::sendMessage`, `Emulator/Store/Controllers/ProductController::update(int $id)`).

## Template
```php
<?php

namespace Http\<Area>\<Module>\Controllers;

use App\Controllers\ResourceController;
use Http\<Area>\<Module>\Filters\<Entity>Filter;
use Http\<Area>\<Module>\Requests\<Entity>\<Entity><Action>Request;
use Http\<Area>\<Module>\Requests\<Entity>Request;
use Illuminate\Http\JsonResponse;
use Modules\<Module>\Enums\<Entity>StatusEnum;
use Modules\<Module>\Models\<Entity>;
use Modules\<Module>\Search\<Entity>Search;

class <Entity>Controller extends ResourceController
{
    public function __construct()
    {
        parent::__construct(
            model: new <Entity>(),
            search: new <Entity>Search(),
            filter: new <Entity>Filter(),
            formRequestClass: <Entity>Request::class,
        );
    }

    public function <action>(<Entity><Action>Request $request): JsonResponse
    {
        $items = $request->validatedData['items'];
        $request->model->getService()-><action>($items);

        return $this->successResponse();
    }

    public function complete(<Entity> $model): JsonResponse
    {
        $model->safelySave(['status' => <Entity>StatusEnum::COMPLETED->value]);
        return $this->successResponse();
    }
}
```

## Don't
- Don't extend `Illuminate\Routing\Controller` directly; don't call `$request->validate()` / `validator()` in controllers (only exceptions: the anonymous request in `Common/Record/Controllers/CreateController.php` and `ControllerModelHelper`).
- Don't use `X::query()` for list/show of the controller's own resource — `$this->search->query` carries Search + Filter.
- Don't register `Route::bind()` / `{model}` resolution in a controller — `AppServiceProvider` + `resolveRouteModel()` own it.
- Don't call `$model->save()`, `DB::transaction()`, `lockForUpdate()` (zero usages in `http/`).
- Don't `abort()` or throw `\Exception` for business errors (zero `abort(` in `http/`).
- Don't rely on lazy loading — it throws in `local`/`testing`.
- Don't put domain logic in controllers — it belongs in `Modules\<Module>\Services` (via `getService()`); validation in Requests, output shaping in Resources.
- Don't return arrays/models without `response()->json()` or a Resource.
- Don't create DTOs or other new class types in `http/` (DTOs exist only in `http/Common/Record/DTOs`).
