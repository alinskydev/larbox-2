---
name: http-requests-rules
description: Mandatory standards for form requests in http/<Area>/<Module>/Requests and modules/<Module>/Requests (App\Requests\FormRequest, ResourceFormRequest + DeletableFileFieldsTrait, validatedData, uploads), validation helpers in app/Helpers/Validation and custom rules in app/Rules (App\Base\Rule). Use when creating or editing any FormRequest or validation Rule.
---

# HTTP Requests Rules

Location: `http/<Area>/<Module>/Requests/<Entity>Request.php`; extra actions `Requests/<Entity>/<Entity><Action>Request.php` (`Company/Trade/Requests/Purchase/PurchaseAddProductsRequest.php`); plain controllers `Requests/<Controller>/<Action>Request.php` (`Common/Auth/Requests/ResetPassword/CompleteRequest.php`).
Bases: `App\Requests\ResourceFormRequest` (bound to a model), `App\Requests\FormRequest` (no model: login, settings, admin notifications), `App\Excel\Import\ExcelImportFormRequest` (Excel import).
Canonical: `http/Company/Product/Requests/ProductRequest.php`, `http/Company/Trade/Requests/SaleRequest.php`, `http/Company/User/Requests/EmployeeRequest.php`.

## Rules
- Declare rules only in `public function nonLocalizedRules(): array` / `public function localizedRules(): array`. Localized keys are written without locale; `FormRequest::rules()` expands them to `<field>.<lang>` + `present` for every DB language (`CategoryRequest`, `SpecificationRequest` incl. `'options.*.name'`).
- Pipe strings for plain rules (`'nullable|string|max:255'`); arrays when a rule object, `Rule::` helper or spread is involved.
- Nested input uses dot keys (`'profile.first_name'`, `'credential.telegram_chats.*.chat_id'`). Arrays always get a capped parent rule (`'products' => 'required|array|max:500'`) plus item rules (`'roles.*' => 'required|integer|distinct'`, `'products.*.quantity' => ...`).
- `ResourceFormRequest::$model` = route `{model}` (already filtered) or the controller's new model; branch on `$this->model->exists` (`Rule::requiredIf(!$this->model->exists)`). Add `@property <Model> $model` when model-specific members are used (`PurchaseAddProductsRequest`, `UserRequest`).
- Raw input: magic props (`$this->specification_id`, `$this->username`), `request()->post('parent_id')` in the constructor (`ProductRequest`). Inside `additionalValidation()` use `$this->validated('<field>')` — `$validatedData` is filled only after it.
- Rule toolbox:
  - FK / id lists: `new ExistsWithOldRule(model: $this->model, query: X::query()->filterByNestedCompanies())` (optional `errorMessage:`, `pk:`); ids already linked to the model pass even if deleted. Put it on the array field itself for id lists (`roles`, `options`). `model: new Model()` when there is nothing to compare (`App\NestedSet\NestedSetMoveRequest`).
  - Uniqueness: `new UniqueRule(model: $this->model, isFieldLocalized: bool, additionalQuery: fn($query) => ..., shouldShowDetailedErrorMessage: false)` — case-insensitive, includes trashed; `false` hides the record id (usernames in `UserRequest`). `Rule::unique(...)->ignore(...)` only for a related model's field (Admin `CompanyRequest` `director.username`).
  - `new PhoneRule()`; passwords `Password::defaults()` + `'password_confirmation' => ['required_with:password', 'same:password']`.
  - Enums: `Rule::in(XEnum::values())` or `Rule::in([XEnum::A->value, ...])`; enum-keyed blocks `...array_fill_keys(array_map(fn($value) => "record_limits.$value", XEnum::values()), 'required|integer|min:0')` (`SubscriptionRequest`).
  - Files: `ValidationFileRulesHelper::image()|logo()|document()|pdf()|excel()|media()|audio()|imageOrDocument()`, spread when combined (`['required', ...ValidationFileRulesHelper::excel()]`). Money/quantity: `ValidationNumberRulesHelper::amount(isRequired: true)`.
- Uploads: files `protected array $fileUploads = ['logo' => ['source' => 'local', 'folder' => 'images']]` (`Company/Company/Requests/CompanyRequest.php`); images `protected array $imageUploads = ['image_list.*' => ['source' => 'external', 'folder' => '<table>', 'sizes' => [1920, 1080]]]` (`ProductRequest`, `BrandRequest`). Keys are dotted fields, wildcards allowed; the upload result replaces the value in `$validatedData`.
- Replaceable files: spread `...$this->deletableFileFieldsSingleValidation(field: 'image', rules: ValidationFileRulesHelper::image())` or `...Multiple...(field: 'image_list', ...)` — adds required JSON `<field>_old_keys` (`DeletableFileFieldsTrait`).
- Input strings are tag-escaped in `prepareForValidation()`; list fields that must stay raw (passwords) in `protected array $rawFields` (`UserRequest`, `CompanyUpdateCredentialRequest`).
- `protected array $defaults = ['status' => XEnum::Y->value]` fills missing `$validatedData` keys after validation (`SaleRequest`, `SaleReturnRequest`).
- SEO entities: `use SeoMetaFormRequestTrait;` — adds `seo_meta.<lang>` rules, raw handling and the `seo_meta` relation (`BrandRequest`, `CategoryRequest`).
- `prepareForValidation()`: `parent::prepareForValidation()` first (escaping + 409 when sent `updated_at` differs from the model), then `$this->merge([...])` (`Admin/User/Requests/RoleRequest.php`).
- `protected function additionalValidation(): void` — DB/cross-field checks after rules pass: `$this->validator->errors()->add('<field>', __('...'))` then `return;`; 422 is raised automatically (`LoginRequest`, `PurchaseAddProductsRequest`). Reusable checks go to named `protected function validateX()` (`Modules\User\Requests\RoleRequest::validateRoutes`).
- `protected function passedValidation(): void` — `parent::passedValidation()` first, then reshape `$this->validatedData` (`Arr::forget` immutable fields on update, `Arr::pull`) and attach relations with `$this->model->fillRelations(oneToOne: [...], oneToMany: [...], manyToMany: [...])` using `?? []` fallbacks (`EmployeeRequest`, `SpecificationRequest`). Nested pivots: `$director['manyToMany']['roles'] = Arr::pull($director, 'roles')` (Admin `CompanyRequest`). Non-column data for observers goes to public model props (`$this->model->saleProducts`, `SaleRequest`).
- Constructor: `parent::__construct()` first, then guards throwing `<Entity>Exception::CONST`, defaults for new models (`$this->model->type = ...`), precomputed `private` props (`UserRequest`, `ProductRequest`). Assign `$this->model` before `parent::__construct()` only to replace the bound model (`ProfileRequest` → `user()`).
- Request logic shared by areas lives in `modules/<Module>/Requests/<Entity>Request.php` (`Modules\User\Requests\RoleRequest`); area requests `use ... as Base<Entity>Request`, merge `...parent::nonLocalizedRules()` and call its protected helpers (`http/Admin/User/Requests/RoleRequest.php`, `http/Company/User/Requests/RoleRequest.php`).
- Excel import: extend `ExcelImportFormRequest`, set `$excelColumns`, call `checkAndPrepareExcel(lastColumn:, rowLimit:)` and `validateExcelRow(record:, rules:, errorMessages:, row:)` in `additionalValidation()`; the controller reads `$request->fileContent` (`ProductExcelImportProcessRequest`).
- Messages are Russian via `__('...')`; attribute labels come from `__('fields.<field>')` (`FormRequest::attributes()`).
- Custom rules: `app/Rules/<Name>Rule.php` extends `App\Base\Rule`, implements `passes(string $attribute, mixed $value): bool`, sets translated `$this->errorMessage`; dependencies as promoted `private` constructor params (`ExistsWithOldRule`, `UniqueRule`, `PhoneRule`).

## Template
```php
<?php

namespace Http\<Area>\<Module>\Requests;

use App\Helpers\Validation\ValidationFileRulesHelper;
use App\Requests\ResourceFormRequest;
use App\Rules\ExistsWithOldRule;
use App\Rules\UniqueRule;
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
- Don't override `rules()`, `messages()`, `authorize()` or `attributes()` (zero overrides in `http/`); don't write `.ru/.en/.uz` keys by hand.
- Don't use `Rule::exists` (zero usages) or `Rule::unique` for the request model's own fields.
- Don't throw `ValidationException`; add errors via `$this->validator->errors()->add()`.
- Don't save models, dispatch jobs or upload manually in requests (zero `safely*`/`dispatch` in requests) — uploads come from `$fileUploads`/`$imageUploads`, relations from `fillRelations()`.
- Don't read `$request->all()` / `validated()` in controllers — use `$request->validatedData`.
- Don't create `modules/<Module>/Http/Requests` — the shared location is `modules/<Module>/Requests`.
