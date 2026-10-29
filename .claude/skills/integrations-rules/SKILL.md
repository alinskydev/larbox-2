---
name: integrations-rules
description: Mandatory standards for external integrations, covering App\Integration (IntegrationService, IntegrationException, DTOs), integrations/Store/Services, modules/Integration (tokens), modules/History request logs, external file storage (config/integrations.php, App\Casts\ExternalStorage, FormRequest uploads) and the fake APIs in http/Emulator/* (Storage, Store, Telegram). Use when creating or editing an integration service, an emulator endpoint or test, or code that talks to Storage/Store.
---

# Integrations Rules

Service integrations live in `integrations/<Name>/Services/` (namespace `Integrations\<Name>\Services`): `<Name>BaseService extends App\Integration\IntegrationService` holds the transport, and each entity service extends that base. Fake APIs live in `http/Emulator/<Name>/`, loaded only when `!app()->isProduction()`.
Canonical: `integrations/Store/Services/StoreBaseService.php`, `integrations/Store/Services/StoreProductService.php`, `http/Emulator/Store/`. Telegram transport: see telegram-rules.

## IntegrationService (app/Integration)
- Hooks:
  - Abstract, implemented by the base service: `getConfig(): IntegrationServiceConfigDTO`, `headers(): array`, `processRequest(PendingRequest $request): Response`, `ensureSuccessful(Response $response): Response`.
  - Optional (empty by default): `login()` and `ensurePermission()`; the constructor calls `ensurePermission()`.
  - Change transport only in the base service.
- Constructor `__construct(?Company $company = null)`: `$this->company = $company ?? user()->company ?? null`, `$this->config = $this->getConfig()`, `$this->language = app()->getLocale()` (sent as `Accept-Language`).
- `IntegrationServiceConfigDTO(name, url, username, password, hasOwnAccount, historyModel, additional = [])`:
  - URL and default credentials: `config('integrations.<name>.url|username|password')`.
  - A company's own credentials `$this->company->credential-><name>['username'|'password']` win when both are set (`hasOwnAccount = true`). Source: `Modules\Company\Models\Credential` (`store` column, `encrypted:array`; `CredentialObserver::savingDefaults()` fills `{username, password}` with nulls).
- Relogin: `executeWithRelogin()` builds `Http::baseUrl($this->config->url)->withHeaders($this->headers())`. When the status is in `$forcedLoginStatuses` (`[401, 403, 500]`), it calls `login()` once and retries; a second such status goes to `ensureSuccessful()`.
- Tokens: `Modules\Integration\Models\Token` (table `integration_token`, unique `service_name` + `username`, `token` `encrypted` + hidden, no `updated_at`).
  - `headers()` reads the token by `service_name = $this->config->name` and `username = $this->config->username` → `Authorization: Bearer`.
  - `login()` posts the credentials with plain `Http::baseUrl()->post('auth/login')` and, on 200, `Token::query()->updateOrCreate(...)`.
- Errors: `ensureSuccessful()` calls `$this->throwErrorMessage($message)` (body `message` or status) → `IntegrationException($this->config->name, $message)` (`'Произошла ошибка в сервисе ":service" (:message)'`).
  - Rendered as 400 JSON by the fallback in `bootstrap/app.php`.
  - Extends `\Exception`, not `BusinessException`, so it is reported/logged.
  - Don't `abort()` or throw other exceptions from services.
- `sendRequest(IntegrationRequestOptionsDTO $options, array $data = []): string|array`:
  - Stores `requestOptions`, `requestPath`, `requestBody`, runs `processRequest()` through relogin.
  - Saves history before the success check when `shouldSaveHistory` is `true` or a closure returning true for the `Response`.
  - Returns `additional['response_callback']($response)` or `ensureSuccessful($response)->json()`, then `?: []`.
- History row (`saveHistory()`): a clone of `config->historyModel` filled with `request_path`, `request_body`, `response_body`, `response_status` (reshaped by `additional['history_data_callback']`), plus `company_id = $this->company?->id` and `additional` (from `setAdditionalData()`), saved with `safelySave()`.
  - Store uses `Modules\History\Models\StoreHttpLog` (connection `history`, table `history_store`, observers CreatorId, CompanyId, IP, `StoreHttpLogObserver`). It is written outside the caller's `main` transaction.
  - Read-only at `admin/history/store-http-log` (index, show; `UserSuperadminMiddleware`); search window max 1 day (`Modules\History\Helpers\SearchHelper::createdAtInterval()`); rows older than 1 month are removed by `app:process_db_daily`.

## Entity services
- Name them `Store<Entity>Service extends StoreBaseService`, one public method per remote action returning `array`: `list()`, `create(array $data)`, `update(int $id, array $data)`, `delete(int $id)`, each `return $this->sendRequest(options: new IntegrationRequestOptionsDTO(...), data: $data);`.
- `IntegrationRequestOptionsDTO(method, path, shouldSaveHistory, additional = [])`:
  - `method`: upper-case HTTP verb (base lowercases it for `PendingRequest`).
  - `path`: relative to the base URL (`"product/update/$id"`).
  - `shouldSaveHistory`: `true` for mutating calls; reads use `fn(Response $response) => $response->status() !== 200` (log failures only).
  - `additional`: only `response_callback` / `history_data_callback`, when the default JSON or history shape doesn't fit.
- Daily limits:
  - `protected function ensureDailyLimit(): void` → `DailyLimitHelper::ensure(entityType: DailyLimitEntityTypeEnum::COMPANY, entityId: $this->company->id, actionType: DailyLimitActionTypeEnum::INTEGRATION, actionName: DailyLimitIntegrationActionNameEnum::<CASE>->value)`; call it first in the limited method (`StoreProductService::create()`).
  - The decrease happens in the history observer on a 2xx: `StoreHttpLogObserver::created()` switches on `request_path` (`case 'product/create'`) → `DailyLimitHelper::decrease(..., defaultValue: DailyLimitIntegrationActionNameEnum::limit(...))`. So a limited call needs `shouldSaveHistory: true` and a `case` there.
  - Add the new case to `labels()` and `limit()` of `Modules\System\Enums\DailyLimitIntegrationActionNameEnum`.
- Callers instantiate the service directly (company defaults to `user()->company`) and chain `setAdditionalData([...])` for history context: `(new StoreProductService())->setAdditionalData(['brand_id' => $model->brand->id])->create([...])` in `modules/Product/Observers/ProductObserver.php` (`created`, `updated`, `deleted`; synchronous HTTP inside the save).

## External storage (Storage)
- Storage doesn't use `IntegrationService`: raw `Http::withHeaders(RequestHelper::storageHttpHeaders())` (`Accept` + `Storage-Secret-Key` = `config('integrations.storage.secret_key')`) plus `->onError(function ($error) { ErrorHelper::abortProjectNotWorkingError('Storage', $error); })`, which throws `IntegrationException`.
- Upload: `$imageUploads` / `$fileUploads` in the FormRequest, keyed by field pattern (`'image'`, `'image_list.*'`), with `'source' => 'external'`, `'folder' => '<table>'`, `'sizes' => [1920, 1080]`. `FormRequest::saveFiles()` posts `files[]` + `type`, `folder`, `size` (`1920x1080`) to `<storage.url>/api/upload` and stores `files[0]` (`http/Company/Product/Requests/BrandRequest.php`, `ProductRequest.php`). `'source' => 'local'` uses `FileHelper` / `ImageHelper` instead.
- Model casts `App\Casts\ExternalStorage\{AsFile, AsFiles, AsImage, AsImages}`:
  - File casts return `config('integrations.storage.url') . "/$path"`.
  - Image casts: `AsImage::class . ':<action>_<sizes>'` (`':scaleDown_100|500'`). They need a `<field>_thumbs` json column (default `'[]'`) and a `'<field>_thumbs' => 'array'` cast; `set()` resets thumbs to `'[]'`.
  - Thumbs are fetched lazily from `/api/get/images` (`asForm`) and stored with `updateQuietly()` (`modules/Product/Models/Brand.php`, `Product.php`); `App\Base\Model::attributesToArray()` adds them to the output. Exclude `<field>_thumbs` from `audit_record_action` (`Product::observerParams()`).

## Emulators (http/Emulator/<Name>)
- `routes/api.php` loads `glob('http/Emulator/*/routes.php')` under prefix `emulator` only when not in production, inside the common `api` group, without auth.
- Env points there (`.env.example`): `STORE_URL=${APP_URL}/emulator/store` (+ `STORE_USERNAME`, `STORE_PASSWORD`), `STORAGE_URL=${APP_URL}/emulator/storage` (+ `STORAGE_SECRET_KEY`), `TELEGRAM_BOT_URL=${APP_URL}/emulator/telegram`.
- `routes.php` uses `Route::prefix('<name>')` and mirrors the real method and path; every route is named.
  - Store: `post auth/login`, `get product/list`, `post product/{create,update/{id},delete/{id}}`.
  - Storage: `post api/get/images` (`get-images`), `post api/upload` (`upload`).
  - Telegram: see telegram-rules.
- Controllers: `Http\Emulator\<Name>\Controllers\<Entity>Controller extends App\Controllers\Controller` (`AuthController`, `ProductController`, `StorageController`, `TelegramController`).
  - They return a fake `response()->json([...])` shaped like the real API (tokens/ids from `StringHelper::uuid()`; Storage maps each posted `files` item to `['original' => ['raw' => 'test_data/images/1.png']]`) or `$this->successResponse()`.
  - No FormRequests, validation or DB access; at most `request()` reads.
- Tests: `http/Emulator/<Name>/Tests/<Entity>Test.php`, one `test_<action>` per route calling `$this->sendRequest(method: ..., path: ...)`. `_TestCase extends PostmanTestCase` sets only `$requestUrl = 'emulator/<name>'` (no `$userId`).
- Every new remote path needs a matching emulator route, controller method and test.

## Template
```php
<?php

namespace Integrations\Store\Services;

use App\Integration\DTOs\IntegrationRequestOptionsDTO;
use Illuminate\Http\Client\Response;

class Store<Entity>Service extends StoreBaseService
{
    public function list(): array
    {
        return $this->sendRequest(
            options: new IntegrationRequestOptionsDTO(
                method: 'GET',
                path: '<entity>/list',
                shouldSaveHistory: fn(Response $response) => $response->status() !== 200,
            ),
        );
    }

    public function update(int $id, array $data): array
    {
        return $this->sendRequest(
            options: new IntegrationRequestOptionsDTO(
                method: 'POST',
                path: "<entity>/update/$id",
                shouldSaveHistory: true,
            ),
            data: $data,
        );
    }
}
```

## Don't
- Don't call `Http::` in entity services, observers or controllers; go through `sendRequest()`. Raw `Http::` appears only in a base `login()`, the ExternalStorage image casts and `FormRequest::saveFiles()`.
- Don't hardcode URLs, credentials or tokens; use `config('integrations.*')`, `Credential` and `Token`.
- Don't write history rows by hand or decrease integration daily limits in the service.
- Don't add a remote path without a matching emulator route and test, and don't put emulator code outside `http/Emulator`.
