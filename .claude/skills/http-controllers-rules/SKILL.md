---
name: http-controllers-rules
description: Mandatory standards for HTTP controllers in http/<Area>/<Module>/Controllers (resource, nested-set, plain and invokable controllers, controller traits) and their bases in app/Controllers, app/NestedSet and app/Helpers/Controller. Use when creating or editing any controller or controller action.
---

# HTTP Controllers Rules

Location: `http/<Area>/<Module>/Controllers/<Entity>Controller.php`, namespace `Http\<Area>\<Module>\Controllers`.
Bases: `App\Controllers\ResourceController` (CRUD), `App\NestedSet\NestedSetController` (tree entities), `App\Controllers\Controller` (everything else).
Canonical: `http/Company/Trade/Controllers/PurchaseController.php`, `http/Company/Stock/Controllers/StockController.php`, `http/Common/User/Controllers/ProfileController.php`.

## Rules
- CRUD entity → extend `ResourceController`; the constructor only calls `parent::__construct()` with named args `model: new X()`, `search: new XSearch()`, `filter: new XFilter()`, `formRequestClass: XRequest::class`. Model/Search come from `Modules\<Module>\Models|Search`, Filter/Request from the same HTTP module.
- Pass only what is used: read-only controllers omit `formRequestClass` (`Company/Trade/Controllers/SaleProductController.php`); `filter` is omitted only for unscoped admin data (`Admin/System/Controllers/LanguageController.php`, `Admin/User/Controllers/UserController.php`); `search` omitted when there is no list (`Admin/User/Controllers/NotificationController.php`). `resourceClass:` defaults to `$model->resourceClass`; pass it only for a different Resource.
- Controller, Filter and Request are named after the endpoint entity, not the model: `Company/User/Controllers/EmployeeController.php` = `User` + `UserSearch` + `EmployeeFilter` + `EmployeeRequest`.
- Tree entities extend `NestedSetController` → inherits `tree()` and `move(NestedSetMoveRequest $request)` (`Company/Product/Controllers/CategoryController.php`). There is no AdjacencyList controller (`app/AdjacencyList/` is empty).
- Inherited actions: `index()`, `show(int $id)`, `create(ValidatesWhenResolved $request)`, `update(ValidatesWhenResolved $request)`, `delete(Model $model)`, `restore(int $id)`; `deleteMultiple()`/`restoreMultiple()` exist but are not routed. Never reimplement them; override only to extend, calling `parent::`.
- Overridden `create`/`update` keep `ValidatesWhenResolved $request` (the constructor binds `formRequestClass` to that contract) and use `$request->model` / `$request->validatedData` (`UserController::update`, Admin `NotificationController::create`).
- Overridden `show` keeps `show(int $id)` and calls `parent::show($id)` or reads `$this->search->query->findOrFail($id)` (`Common/User/Controllers/NotificationController::show`, `Company/Product/Controllers/ProductController::show`).
- `$this->search->query` already has `with/filter/sort/show/page_size` and the Filter applied — use it in custom list actions (`NotificationController::markAllSeen`).
- Record actions on `{model}` type-hint the concrete model (`complete(Purchase $model)`, `authAs(User $model)`); with a `filter` the binding goes through `Filter::process()`, so foreign records are unreachable.
- Actions with input type-hint a concrete request (`addProducts(PurchaseAddProductsRequest $request)`), read `$request->validatedData[...]` into variables, then delegate domain work to the model service: `$request->model->getService()->addProducts($products)` (`PurchaseController`, `SaleController::return`) or `$request->model->safelySave($request->validatedData)` (`Admin/Company/Controllers/CompanyController::updateCredential`).
- Status switches: `$model->safelySave(['status' => XStatusEnum::Y->value])` (`PurchaseController::complete`). One validated field: `ControllerModelHelper::setValues(model:, values:, rules:, condition:, conditionErrorMessage:)` (`BrandController::setName`); same helper has `toggleValue()`, `delete()`, `checkCondition()`.
- Persist only via `safelySave()` / `safelyDelete()` / `safelyRestore()` / `safelyDBProcess()`; locks via `DatabaseTransactionHelper::lock()` inside `safelyDBProcess()` (`NestedSetController::move`). Bulk flag updates on the filtered query use `->getQuery()->update([...])` (`NotificationController`).
- Heavy work → `dispatch(new XJob(...))` + `$this->successResponse()` (Admin `NotificationController::create`, `ProductControllerExcelImportTrait::excelImportProcess`).
- Totals on `index`: `$response = parent::index(); $data = $response->getData(true); $data['summary'] = $this->search->query->cloneForSummary()->select(...)->getQuery()->first(); return $response->setData($data);` (`StockController::index`).
- Alternate formats via `?format=`: `excel` in `index` → `ExcelExportHelper::process(jobClass:, query: $this->search->query)`; `pdf` in `show` → `PdfHelper::download(path:, name:, params:)` (`Company/Product/Controllers/ProductController.php`).
- Responses: `response()->json($data)` with the payload prepared in a variable; `$this->successResponse()` when no payload; `$this->successResponseWithAccessToken($user)` after login/password changes (`AuthController::login`, `EmployeeController::update`, `ProfileController::update`); files via `ControllerStreamHelper::download(name:, path:|content:, shouldBeDeleted:)` (`NotificationController::download`) or `ExcelImportHelper::downloadExample($path)`. Single resource: `$model->resourceClass::make($model)`.
- Every action declares a return type: `JsonResponse`, `StreamedResponse`, `JsonResponse|StreamedResponse` (`Symfony\Component\HttpFoundation\Response` in `ResetPasswordController`).
- Errors: business errors throw `Modules\<Module>\Exceptions\<Entity>Exception::CONST` (`UserController::authAs`, `NotificationController::download`; see exceptions-rules). `abort(404)` only hides inaccessible endpoints/types (`Common/Record/Controllers/IndexController.php`). Missing records from `findOrFail`/binding render 400 «Запись не найдена» (`bootstrap/app.php`).
- Many extra actions → trait `Controllers/Traits/<Entity>/<Entity>Controller<Feature>Trait.php` with `@mixin Controller`, `use`d in the controller (`ProductControllerExcelImportTrait`).
- Single-endpoint handlers are invokable `Controller`s with `__invoke()` and `private` helpers (`Admin/History/Controllers/DictionaryController.php`, `Common/System/Controllers/SystemController.php`).
- Non-CRUD endpoints (auth, profile, settings) extend `Controller` and type-hint concrete requests; current user via `user()` (`Admin/System/Controllers/SettingController.php`, `Common/Auth/Controllers/ResetPasswordController.php`).
- `common/record/{type}` dropdowns: add a `match ($type)` arm with `RecordIndexParamsDTO(query:, field:, additionalFields:, isAccessible:)` / `RecordCreateParamsDTO(model:, nonLocalizedRules:, localizedRules:, isAccessible:)` (`Common/Record/Controllers/IndexController.php`, `CreateController.php`); there is no Filter there, scope the query with `filterBy*()` yourself.
- Areas: **Admin** — back-office for admin users, scoping by administered companies; **Company** — tenant endpoints, every resource controller has a Filter; **Common** — auth, profile, notifications, record lists, plain or invokable controllers; **Emulator** — fakes of Storage/Store/Telegram APIs: plain `Controller`, hardcoded responses, no DB, method names mirror the external API (`Emulator/Telegram/Controllers/TelegramController::sendMessage`).

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
- Don't call `$model->save()`, `DB::transaction()`, `lockForUpdate()` (zero usages in `http/`).
- Don't `abort(400, ...)` or throw `\Exception` for business errors (zero `abort(400` in `http/`).
- Don't put domain logic in controllers — it belongs in `Modules\<Module>\Services` (via `getService()`); validation in Requests, output shaping in Resources.
- Don't return arrays/models without `response()->json()` or a Resource.
- Don't create DTOs or other new class types in `http/` (DTOs exist only in `http/Common/Record/DTOs`).
