---
name: http-requests-rules
description: Mandatory standards for form requests in http/<Area>/<Module>/Requests and modules/<Module>/Requests (App\Requests\FormRequest, ResourceFormRequest + DeletableFileFieldsTrait, validatedData, uploads), validation helpers in app/Helpers/Validation and custom rules in app/Rules (App\Base\Rule). Use when creating or editing any FormRequest or validation Rule.
---

# HTTP Requests Rules

Location: `http/<Area>/<Module>/Requests/<Entity>Request.php`; extra actions `Requests/<Entity>/<Entity><Action>Request.php` (`Company/Trade/Requests/Purchase/PurchaseAddProductsRequest.php`); plain controllers `Requests/<Controller>/<Action>Request.php` (`Common/Auth/Requests/ResetPassword/CompleteRequest.php`).
Bases: `App\Requests\ResourceFormRequest` (bound to a model, 22 requests), `App\Requests\FormRequest` (no model: login, reset password, settings, admin notifications), `App\Excel\Import\ExcelImportFormRequest` (Excel import), `App\NestedSet\NestedSetMoveRequest` (tree move, ready-made).
Canonical: `http/Company/Product/Requests/ProductRequest.php`, `http/Company/Trade/Requests/SaleRequest.php`, `http/Company/User/Requests/EmployeeRequest.php`, `http/Admin/Company/Requests/CompanyRequest.php`.

## Rules
- Wiring: CRUD `create`/`update` get the request via `formRequestClass: <Entity>Request::class` in the controller's `parent::__construct()` (bound to `ValidatesWhenResolved`); extra actions type-hint it (`PurchaseController::addProducts(PurchaseAddProductsRequest $request)`). Controllers read only `$request->validatedData` and `$request->model`.
- Declare rules only in `public function nonLocalizedRules(): array` / `public function localizedRules(): array` (protected in the base, public in every subclass). Localized keys are written without locale; `FormRequest::rules()` expands them to `<field>.<lang>` + `present` for every DB language (`CategoryRequest`, `SpecificationRequest` incl. `'options.*.name'`).
- Pipe strings for plain rules (`'nullable|string|max:255'`); arrays when a rule object, `Rule::` helper or spread is involved.
- Nested input uses dot keys (`'profile.first_name'`, `'credential.telegram_chats.*.chat_id'`). Arrays always get a capped parent rule (`'products' => 'required|array|max:500'`) plus item rules (`'roles.*' => 'required|integer|distinct'`). Group blocks of nested fields with a blank line.
- `ResourceFormRequest::$model` = route `{model}` (already scoped by the controller filter) or the controller's new model; branch on `$this->model->exists` (`Rule::requiredIf(!$this->model->exists)`). Add class PHPDoc `@property <Model> $model` when model-specific members are used (`UserRequest`, `PurchaseAddProductsRequest`, `SaleReturnRequest`).
- Raw input: magic props (`$this->specification_id`, `$this->parent_id`, `$this->username`), `$this->only(...)` (`LoginRequest`), `request()->post('parent_id')` in the constructor (`ProductRequest`). Inside `additionalValidation()` use `$this->validated('<field>')` — `$validatedData` is filled only after it.
- Rule toolbox:
  - FK / id lists: `new ExistsWithOldRule(model: $this->model, query: X::query()->filterByNestedCompanies())` (optional `pk:`, `errorMessage:`); ids already linked to the model pass even if deleted. Scope the query like the HTTP Filter (`filterByNestedCompanies()`, `filterByOwnRecords(RolePermissionHelper::ROUTE_...)`, `filterByAdministeredCompanies('id')`) or rely on model `$autoFilters` (`Option::query()`). Put it on the array field for id lists (`roles`, `options`). `model: new Model()` when there is nothing to compare (`NestedSetMoveRequest`).
  - Ids inside object lists (`products.*.product_id`): one `whereIn(...)->pluck('id')` in `additionalValidation()` and add the error to `"products.$index.product_id"` (`SaleRequest`, `PurchaseAddProductsRequest`).
  - Uniqueness: `new UniqueRule(model: $this->model, isFieldLocalized: bool, additionalQuery: fn($query) => ..., shouldShowDetailedErrorMessage: false)` — case-insensitive (`whereLower`), excludes the model, includes trashed; `false` hides the duplicate id (usernames). `Rule::unique(...)->ignore(...)` only for a related model's field (Admin `CompanyRequest` `director.username`).
  - `new PhoneRule()` (`998xxyyyzzzz`); passwords `Password::defaults()` + `'password_confirmation' => ['required_with:password', 'same:password']`; `'is_confirmed' => 'accepted'` for confirm checkboxes.
  - Enums: `Rule::in(XEnum::values())` or `Rule::in([XEnum::A->value, ...])`; enum-keyed blocks `...array_fill_keys(array_map(fn($value) => "record_limits.$value", XEnum::values()), 'required|integer|min:0')` (`SubscriptionRequest`).
  - Files: `ValidationFileRulesHelper::image()|logo()|document()|pdf()|excel()|media()|audio()|imageOrDocument()`, spread when combined (`['required', ...ValidationFileRulesHelper::excel()]`). Money/quantity: `ValidationNumberRulesHelper::amount(isRequired: true)` (`decimal:0,2`, min `0.01`).
- Uploads: `protected array $fileUploads = ['logo' => ['source' => 'local', 'folder' => 'images']]` (`CompanyRequest`, `LanguageRequest`, `SettingRequest`); `protected array $imageUploads = ['image_list.*' => ['source' => 'external', 'folder' => '<table>', 'sizes' => [1920, 1080]]]` (`ProductRequest`, `BrandRequest`). Keys are dotted fields, wildcards allowed; the stored path replaces the value in `$validatedData`. Uploaded files not matched by either list are dropped from `$validatedData`.
- Replaceable files: spread `...$this->deletableFileFieldsSingleValidation(field: 'logo', rules: ValidationFileRulesHelper::image())` or `...Multiple...(field: 'image_list', ...)` — adds required JSON `<field>_old_keys` (optional with `areKeysRequired: false`); empty keys clear a single file, multiple keeps the listed old files plus new ones.
- Input strings are tag-escaped in `prepareForValidation()`; fields that must stay raw (passwords) go to `protected array $rawFields` (`EmployeeRequest`, `CompanyUpdateCredentialRequest` `'credential.store.new_password'`).
- `protected array $defaults = [...]` fills missing `$validatedData` keys after validation via `data_fill` (`SaleRequest` `status`, `SaleReturnRequest` `products`/`transactions` `[]`).
- SEO entities: `use SeoMetaFormRequestTrait;` — adds `seo_meta.<lang>` rules, raw handling and the `seo_meta` oneToOne relation (`BrandRequest`, `CategoryRequest`, `ProductRequest`).
- `prepareForValidation()`: `parent::prepareForValidation()` first (escaping + 409 when sent `updated_at` differs from the model), then `$this->merge([...])` (`Admin/User/Requests/RoleRequest.php`, `Modules\User\Requests\RoleRequest`).
- `protected function additionalValidation(): void` — DB/cross-field checks after rules pass: `$this->validator->errors()->add('<field>', __('...'))` then `return;`; 422 is raised automatically (`LoginRequest`, `CompleteRequest`, Admin `CompanyRequest`). Reusable checks go to a named `protected function validateX()` (`Modules\User\Requests\RoleRequest::validateRoutes`).
- `protected function passedValidation(): void` — `parent::passedValidation()` first, then reshape `$this->validatedData` (`Arr::forget` immutable fields on update, `Arr::pull`, `array_map` pivot rows) and attach relations with `$this->model->fillRelations(oneToOne: [...], oneToMany: [...], manyToMany: [...])` using `?? []` fallbacks (`EmployeeRequest`, `SpecificationRequest`). Nested pivots: `$director['manyToMany']['roles'] = Arr::pull($director, 'roles')` (Admin `CompanyRequest`). Non-column data for observers/services goes to public model props (`$this->model->saleProducts`, `SaleRequest`).
- Constructor: `parent::__construct()` first, then guards throwing `<Entity>Exception` constants (`throw new UserException(UserException::UPDATING_FORBIDDEN)` in `UserRequest`, `RoleException::OWN_ROLE_UPDATING` in `Modules\User\Requests\RoleRequest`), defaults for new models (`$this->model->type = ...`), precomputed `private` props (`ProductRequest::$parentId`). Assign `$this->model` before `parent::__construct()` only to replace the bound model (`ProfileRequest` → `user()`).
- Request logic shared by areas lives in `modules/<Module>/Requests/<Entity>Request.php` (only `Modules\User\Requests\RoleRequest`); area requests `use ... as BaseRoleRequest`, merge `...parent::nonLocalizedRules()` and call its protected helpers (`http/Admin/User/Requests/RoleRequest.php`, `http/Company/User/Requests/RoleRequest.php`).
- Excel import: extend `ExcelImportFormRequest`, set `$excelColumns`, call `checkAndPrepareExcel(lastColumn:, rowLimit:)` and `validateExcelRow(record:, rules:, errorMessages:, row:)` in `additionalValidation()`; the controller reads `$request->fileContent` (`ProductExcelImportProcessRequest`).
- Messages are Russian via `__('...')`; attribute labels come from `__('fields.<field>')` (`FormRequest::attributes()`, locale suffix stripped).
- Custom rules: `app/Rules/<Name>Rule.php` extends `App\Base\Rule` (implements `ValidationRule`), implements `passes(string $attribute, mixed $value): bool`, sets translated `$this->errorMessage`; dependencies as promoted `private` constructor params (`ExistsWithOldRule`, `UniqueRule`, `PhoneRule`).

## Template
```php
<?php

namespace Http\<Area>\<Module>\Requests;

use App\Helpers\Validation\ValidationFileRulesHelper;
use App\Requests\ResourceFormRequest;
use App\Rules\ExistsWithOldRule;
use App\Rules\UniqueRule;
use Modules\<Module>\Exceptions\<Entity>Exception;
use Modules\<Module>\Models\<Related>;

class <Entity>Request extends ResourceFormRequest
{
    protected array $imageUploads = [
        'image' => [
            'source' => 'external',
            'folder' => '<table>',
            'sizes' => [1920, 1080],
        ],
    ];

    public function __construct()
    {
        parent::__construct();

        if ($this->model->exists && $this->model-><flag>) {
            throw new <Entity>Exception(<Entity>Exception::UPDATING_FORBIDDEN);
        }
    }

    public function nonLocalizedRules(): array
    {
        return [
            '<related>_id' => [
                'required',
                'integer',
                new ExistsWithOldRule(
                    model: $this->model,
                    query: <Related>::query()->filterByNestedCompanies(),
                ),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                new UniqueRule(
                    model: $this->model,
                    isFieldLocalized: false,
                ),
            ],
            ...$this->deletableFileFieldsSingleValidation(
                field: 'image',
                rules: ValidationFileRulesHelper::image(),
            ),

            '<items>' => 'array|max:10',
            '<items>.*' => 'required|integer|distinct',
        ];
    }

    protected function passedValidation(): void
    {
        parent::passedValidation();

        $this->model->fillRelations(
            manyToMany: [
                '<items>' => $this->validatedData['<items>'] ?? [],
            ],
        );
    }
}
```

## Don't
- Don't override `rules()`, `messages()`, `authorize()` or `attributes()` (zero overrides outside `app/Requests`); don't write `.ru/.en/.uz` keys by hand.
- Don't use `Rule::exists` (zero usages) or `Rule::unique` for the request model's own fields.
- Don't throw `ValidationException`; add errors via `$this->validator->errors()->add()`.
- Don't `abort()` in requests — business guards throw `modules/<Module>/Exceptions` (400); the only abort is the base 409 in `ResourceFormRequest::prepareForValidation()`.
- Don't save models, dispatch jobs or upload manually in requests (zero `safely*`/`dispatch`) — uploads come from `$fileUploads`/`$imageUploads`, relations from `fillRelations()`, persistence from the controller.
- Don't read `$request->all()` / `validated()` in controllers — use `$request->validatedData`.
- Don't create `modules/<Module>/Http/Requests` — the shared location is `modules/<Module>/Requests`.
