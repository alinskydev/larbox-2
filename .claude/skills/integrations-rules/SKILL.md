---
name: integrations-rules
description: Mandatory standards for external integrations, covering App\Integration (IntegrationService, IntegrationException, DTOs), integrations/Store/Services, modules/Integration (tokens), modules/History request logs, external file storage (config/integrations.php, App\Casts\ExternalStorage, FormRequest uploads) and the fake APIs in http/Emulator/* (Storage, Store, Telegram). Use when creating or editing an integration service, an emulator endpoint or test, or code that talks to Storage/Store.
---

# Integrations Rules

Service integrations live in `integrations/<Name>/Services/` (namespace `Integrations\<Name>\Services`). `<Name>BaseService extends App\Integration\IntegrationService` holds the transport, and each entity service extends that base. Fake APIs live in `http/Emulator/<Name>/` and are registered only when `!app()->isProduction()`.
Canonical: `integrations/Store/Services/StoreBaseService.php`, `integrations/Store/Services/StoreProductService.php`, `http/Emulator/Store/`.

## IntegrationService (app/Integration)
- Hooks:
  - The base service implements the abstract ones: `getConfig(): IntegrationServiceConfigDTO`, `headers(): array`, `processRequest(PendingRequest $request): Response`, `ensureSuccessful(Response $response): Response`.
  - Optional: `login()` and `ensurePermission()`. The constructor calls `ensurePermission()`.
  - Change transport only in the base service.
- Constructor: `$this->company = $company ?? user()->company ?? null`, and `$this->language = app()->getLocale()`. Send the language as the `Accept-Language` header.
- `IntegrationServiceConfigDTO(name, url, username, password, hasOwnAccount, historyModel, additional)`:
  - The URL and default credentials come from `config('integrations.<name>.url|username|password')`.
  - A company's own credentials in `$this->company->credential-><name>['username'|'password']` win when both are set, which makes `hasOwnAccount` true. They come from `Modules\Company\Models\Credential` (`encrypted:array` cast, defaults in `CredentialObserver::savingDefaults()`).
- Relogin: `executeWithRelogin()` builds `Http::baseUrl($this->config->url)->withHeaders($this->headers())`. When the status is in `$forcedLoginStatuses` (`[401, 403, 500]`), it calls `login()` once and retries. A second failure goes to `ensureSuccessful()`.
- Tokens: `Modules\Integration\Models\Token` (table `integration_token`, unique `service_name` + `username`, `token` encrypted and hidden).
  - `headers()` reads the token by `service_name = $this->config->name` and the current username.
  - `login()` posts the credentials with plain `Http::baseUrl()` and, on 200, calls `Token::query()->updateOrCreate(...)`.
- Errors: `ensureSuccessful()` calls `$this->throwErrorMessage($message)`, which throws `IntegrationException($this->config->name, $message)` (`'Произошла ошибка в сервисе ":service" (:message)'`).
  - It is rendered as a 400 JSON response.
  - It extends `\Exception`, not `BusinessException`, so it is logged.
  - Don't `abort()` or throw other exceptions from services.
- `sendRequest(IntegrationRequestOptionsDTO $options, array $data = [])`:
  - Saves history before the success check when `shouldSaveHistory` is `true`, or is a closure that returns true for the `Response`.
  - Then returns `additional['response_callback']($response)`, or `ensureSuccessful($response)->json()`, with `?: []`.
- History row:
  - It is a clone of `config->historyModel`, filled with `request_path`, `request_body`, `response_body`, `response_status`, `company_id`, and `additional` (from `setAdditionalData()`), then saved with `safelySave()`. `additional['history_data_callback']` can reshape the data.
  - Store uses `Modules\History\Models\StoreHttpLog` (connection `history`, table `history_store`, observers CreatorId, CompanyId, IP and `StoreHttpLogObserver`).
  - It is exposed read-only at `admin/history/store-http-log` (index, show; superadmin only).

## Entity services
- Name them `Store<Entity>Service extends StoreBaseService`, with one public method per remote action: `list()`, `create(array $data)`, `update(int $id, array $data)`, `delete(int $id)`. Each one does `return $this->sendRequest(options: new IntegrationRequestOptionsDTO(...), data: $data);`.
- `IntegrationRequestOptionsDTO` arguments:
  - `method`: upper-case HTTP verb.
  - `path`: relative to the base URL (`"product/update/$id"`).
  - `shouldSaveHistory`: `true` for mutating calls. For reads, `fn(Response $response) => $response->status() !== 200` (log failures only).
  - `additional`: only when the default JSON or history shape doesn't fit.
- Daily limits:
  - Add `protected function ensureDailyLimit(): void`, which calls `DailyLimitHelper::ensure(entityType: DailyLimitEntityTypeEnum::COMPANY, entityId: $this->company->id, actionType: DailyLimitActionTypeEnum::INTEGRATION, actionName: DailyLimitIntegrationActionNameEnum::<CASE>->value)`. Call it first in the limited method.
  - The decrease happens in the history observer on a 2xx, keyed by `request_path` (`StoreHttpLogObserver::created()` → `DailyLimitHelper::decrease(..., defaultValue: DailyLimitIntegrationActionNameEnum::limit(...))`). So a limited call must use `shouldSaveHistory: true`.
  - Add the new case to `labels()` and `limit()` of `Modules\System\Enums\DailyLimitIntegrationActionNameEnum`.
- Callers instantiate the service directly; the company defaults to `user()->company`. Chain `setAdditionalData([...])` for history context: `(new StoreProductService())->setAdditionalData(['brand_id' => $model->brand->id])->create([...])` (`modules/Product/Observers/ProductObserver.php`, in `created`, `updated` and `deleted`).

## External storage (Storage)
- Storage doesn't use `IntegrationService`. It is called with raw `Http::withHeaders(RequestHelper::storageHttpHeaders())` (`Accept` + `Storage-Secret-Key` from `config('integrations.storage.secret_key')`) plus `->onError(function ($error) { ErrorHelper::abortProjectNotWorkingError('Storage', $error); })`. That error helper throws `IntegrationException`.
- Upload: set `$imageUploads` or `$fileUploads` in the FormRequest with `'source' => 'external'`, `'folder' => '<table>'` and `'sizes' => [1920, 1080]`. `FormRequest::saveFiles()` posts to `<storage.url>/api/upload` and stores the returned path (`http/Company/Product/Requests/BrandRequest.php`, `ProductRequest.php`).
- Model casts `App\Casts\ExternalStorage\{AsFile, AsFiles, AsImage, AsImages}`:
  - File casts return `config('integrations.storage.url') . "/$path"`.
  - Image casts are written as `AsImage::class . ':<action>_<sizes>'` (`':scaleDown_100|500'`). They need a `<field>_thumbs` json column (default `'[]'`) and a `'<field>_thumbs' => 'array'` cast.
  - Thumbs are fetched lazily from `/api/get/images` and stored with `updateQuietly()` (`modules/Product/Models/Brand.php`, `Product.php`). `App\Base\Model::attributesToArray()` adds them to the output.

## Emulators (http/Emulator/<Name>)
- `routes/api.php` loads `glob('http/Emulator/*/routes.php')` under prefix `emulator` only when not in production, without auth.
- Config points there through env:
  - `STORAGE_URL=${APP_URL}/emulator/storage` and `TELEGRAM_BOT_URL=${APP_URL}/emulator/telegram` (`.env.example`).
  - `STORE_URL` belongs at `${APP_URL}/emulator/store`. `STORE_*` keys are missing from `.env.example`.
- Routes: `routes.php` uses `Route::prefix('<name>')` and mirrors the real method and path. Every route is named.
  - Store: `post auth/login`, `product/{list,create,update/{id},delete/{id}}`.
  - Storage: `post api/get/images`, `post api/upload`.
  - Telegram: see telegram-rules.
- Controllers are `Http\Emulator\<Name>\Controllers\<Entity>Controller extends App\Controllers\Controller` (`AuthController`, `ProductController`, `StorageController`, `TelegramController`).
  - They return a fake `response()->json([...])` (ids and tokens from `StringHelper::uuid()`) or `$this->successResponse()`.
  - They have no requests, validation or DB access.
- Tests: `http/Emulator/<Name>/Tests/<Entity>Test.php`, with one `test_<action>` per route that calls `$this->sendRequest(method: ..., path: ...)`. `_TestCase extends PostmanTestCase` sets only `$requestUrl = 'emulator/<name>'`, with no `$userId`.
- Every new remote path needs a matching emulator route, a controller method and a test.

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
- Don't call `Http::` in entity services, observers or controllers; go through `sendRequest()`. Raw `Http::` appears only in a base `login()`, the Storage casts and `FormRequest::saveFiles()`.
- Don't hardcode URLs, credentials or tokens. Use `config('integrations.*')`, `Credential` and `Token`.
- Don't write history rows by hand or decrease integration daily limits in the service.
- Don't add a remote path without a matching emulator route and test, and don't put emulator code outside `http/Emulator`.
