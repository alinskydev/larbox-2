---
name: http-tests-rules
description: Mandatory standards for all tests — API feature tests in http/<Area>/<Module>/Tests/<Entity>/ (PostmanTestCase), domain tests in modules/<Module>/Tests/<Entity>/ (DomainTestCase), unit tests in app/Testing/Unit/Tests/ — and phpunit.xml suites. Use when creating, editing or running any test.
---

# Tests

Base classes in `app/Testing/` (`App\Testing\`), all with `CreatesApplicationTrait` (boots `bootstrap/app.php`): `Feature\PostmanTestCase` (API), `Domain\DomainTestCase` (models/services), `Unit\TestCase`. Canonical: `http/Company/Trade/Tests/Sale/`, `modules/Trade/Tests/Sale/`, `app/Testing/Unit/Tests/Rules/PhoneRuleTest.php`.

## Common
- Real PostgreSQL DB from `.env` (no sqlite, no factories, no fakes); data = module seeders loaded by `larbox:migrate`. Seeded users: 1 = admin/superadmin, 2 = director of company 1 (`director_1@local.host`), 3 = director of child company 2 (`director_1_1@local.host`), 4 = employee of company 1 (`employee_1@local.host`); password `User_1234` (not admin).
- Feature and Domain cases `use DatabaseTransactions` on `$connectionsToTransact = ['main', 'history']` — every test is rolled back; PDOs (incl. `telescope`) are cached statically and reused (`beginDatabaseTransaction()` override). `Unit\TestCase` has no transactions: revert DB changes in the test (`ExistsWithOldRuleTest::test_single_passed_exists()`).
- `phpunit.xml` suites: `Unit` (`./app/Testing/Unit/Tests`), `Domain` (`./modules`), `Feature` (`./http/...`), suffix `Test.php` (`_TestCase.php` never runs itself). Directories are toggled manually by commenting: Unit/Domain are commented out now; Feature has a `FOR DEV` block (single folders) and a `FOR FRONTEND DEVELOPER` block (whole areas). `stopOnFailure`/`stopOnError` = true.
- phpunit env: `APP_ENV=testing`, `APP_DEBUG=false` (JSON errors from `bootstrap/app.php`), `CACHE_STORE=array`, `QUEUE_CONNECTION=sync`, `MAIL_MAILER=array`, Telescope on.
- Run: `php artisan larbox:test` (`optimize:clear` → `larbox:migrate` → `test`; blocked outside `local`, `routes/console.php`) or `composer test` on a migrated DB. `larbox:export_postman_collection` deletes `_postman.json`, runs `larbox:test`, writes `storage/larbox/<name>_<date>_collection.json`.
- Methods: `public function test_<case>(): void`, snake_case; `___` separates qualifiers: `test_success___products`, `test_error___not_enough_stock`, `test_type___product___variations`.

## Feature (API) tests
- `http/<Area>/<Module>/Tests/<Entity>/<Action>Test.php`, namespace `Http\<Area>\<Module>\Tests\<Entity>`, extends local `_TestCase extends PostmanTestCase`. Sub-resource: nested folder with own `_TestCase` (`http/Company/Product/Tests/Product/ExcelImport/`); single-entity modules skip `<Entity>` (`http/Common/Auth/Tests/`, `http/Emulator/*/Tests/`).
- `_TestCase`: `public int $userId` + `public string $requestUrl` (route prefix, no leading slash: `company/trade/sale`). `$userId = 1` for `Admin` and authenticated `Common` (`http/Common/User/Tests/Profile/_TestCase.php`), `2` for `Company`; omit for public routes (`http/Common/Auth/Tests/_TestCase.php`, Emulator). Override: `public int $userId = 2;` in the test class (`LogoutTest`) or `$this->authAsUser(3)` inside a test.
- Same scenarios for several users: `test_*` methods in `_TestCase` + thin subclasses setting only `$userId` (`http/Common/Record/Tests/Index/ForAdminGroupTest.php`, `ForCompanyGroupTest.php`).
- One class per route action: `IndexTest`, `ShowTest`, `CreateTest`, `UpdateTest`, `DeleteTest`, custom ones named after the action (`AddProductsTest`, `CancelTest`, `ReturnTest`, `MoveTest`, `AuthAsTest`). Only for registered routes (`->only()`/`->except()` in `routes.php`; `deleteMultiple`/`restoreMultiple` are off in `app/Routing/ResourceRegistrar.php`).
- Requests only via `$this->sendRequest()` with named args `method` (default `'GET'`), `path`, `query`, `body`, `assertStatus` (default 200), `throttleCount`; omit defaults. `POST` create/custom, `PUT` update, `PATCH` record actions (`'1/cancel'`), `DELETE` delete/restore; PUT/PATCH go as POST + `_method`.
- `path` = seeded IDs as string: `'1'`, `'2/restore'`, `'1/add-products'`, `'tree'`. `sendRequest()` appends `path` to `$requestUrl` permanently: one call per test; for repeated calls set `$this->requestUrl` and omit `path` (`http/Common/Record/Tests/Create/_TestCase.php`).
- No explicit assertions — status is checked in `sendRequest()`. `400` business error (`BusinessException`, record not found by ID), `422` validation, `404` missing route/file, `429` throttle.
- Throttle: `throttleCount: 3` sends 4 requests, the last must be 429 (`ResetPassword/SendCodeTest`); if bodies must differ, loop with `assertStatus: $i < 3 ? 200 : 429`.
- Minimal setup through models before the request: `Brand::query()->where('id', 2)->delete()` (restore), `->update([...])`, `user()->roles()->update(['routes' => [...]])` (permissions are real, `RoleMiddleware` runs), `(new AuthCode())->safelySave([...])`. Remove created files (`Notification/DownloadTest`).
- IndexTest: `use SearchFeatureTestTrait;` + `public string $searchClass = XSearch::class;` adds `test_available_sorts`, `test_available_relations`, `test_show_with_deleted`, `test_show_only_deleted`, `test_pagination`. Disable inapplicable by aliasing to private: deleted ones for tables without soft deletes (all `Trade`), `test_available_relations` (`Notification`), `test_available_sorts` (`Category`).
- `test_available_filters()` → `$this->sendRequestWithFilters([...])` with every Search filter: scalars, `XEnum::CASE->value`, ranges `[0, 10000]`, dates `date('Y-m-d', strtotime('-1 day'))`/`date('Y-m-d')` (`'Y-m-d H:i:s'` for datetime), relations `'roles.id' => [1, 2]`, custom filters after a blank line (`'search' => 'product'`). Variants: `test_filter___roots`. Custom shows: `test_available_shows()`. Exports: `test_format_excel` (`'format' => 'excel', 'excel_columns' => [...]`), `test_format_pdf` in ShowTest.
- `App\Testing\Feature\Helpers\FormHelper`: `localized('Option 3')`, `...seoMeta()`, `files()` (fake), `realImage()` (`public/test_data/images/1.png`), `...deletableFiles(field:, files:, oldKeys:)` (`[]` on create, kept keys on update); without upload `'image_old_keys' => '[0]'` / `'[]'`. Real file: `UploadedFile::fake()->createWithContent(..., file_get_contents(base_path(...)))` (`ExcelImport/ProcessTest`).
- Body: own fields, blank line, nested arrays, blank line, `...FormHelper::seoMeta()`. Children: `array_map(fn($index) => [...], range(1, 2))`, `'id' => $index` on update. Shared body: `private function body(...): array`, overridden by spread (`[...$this->body(), 'parent_id' => 3]`) or parameters (`$this->body(quantity: 2, amount: 2000)`); item builders `private function product(int $productId): array`.
- Postman: each test's last request/response is collected (key from class + method, `___` → ` | `) and saved to `storage/larbox/_postman.json` in `tearDownAfterClass()`; class docblock = request comment (none now). `protected bool $shouldExportToPostman = false` excludes a class; extra headers `public array $requestHeaders` (defaults `Accept: application/json`, `Accept-Language: en`).
- Failure throws the JSON response; `TESTING_SHOW_DETAILED_ERROR=true` prints message/file/line/trace and `exit`s.
- `ScenarioTestCase::processTestMethod($class, $method, $callback)` runs another test method without transactions (data persists) — multi-step scenarios only, currently unused.

## Domain tests
- `modules/<Module>/Tests/<Entity>/<Action>Test.php`, namespace `Modules\<Module>\Tests\<Entity>`, local `_TestCase extends App\Testing\Domain\DomainTestCase` with `public int $userId` (`setUser()` in `setUp()`). Scenarios and method names mirror the HTTP test of the same action (`http/Company/Trade/Tests/Sale/CreateTest.php`).
- Implement `getValues(): array` — flat `['dotted.key' => value]` snapshot; shared keys in `_TestCase`, action keys via `[...parent::getValues(), 'sales' => ...]`. Cast numbers `(float)`, enums `SaleStatusEnum::from($sale->status)`.
- One `$this->assertChanges(callback: fn() => ..., expected: ['key' => [before, after]])` per test: every changed key listed, others must stay equal. Error: `error: SaleException::WRONG_PAYMENTS_AMOUNT` (a `BusinessException` constant), no `expected` — callback changes are rolled back; other exceptions are rethrown.
- Actions as helpers calling model/service API: shared `protected` in `_TestCase` (`createSale()` via `safelySave()`, `getSale()`), specific `private` in the test (`returnProducts()`, `refund()` via `getService()->return()`).

## Unit tests
- `app/Testing/Unit/Tests/<Group>/<Class>Test.php`, namespace `App\Testing\Unit\Tests\<Group>`, extends `App\Testing\Unit\TestCase`.
- Instantiate, call, assert, blank-line separated (`$result = $rule->passes('phone', '...'); $this->assertTrue($result);`). Names `test_passed`, `test_failed_<reason>`, `test_<group>_passed_<case>`; groups marked `// Single`, `// Multiple`. User context: `setUser(2)`.

## Template
```php
// http/Company/Trade/Tests/Sale/_TestCase.php
namespace Http\Company\Trade\Tests\Sale;

use App\Testing\Feature\PostmanTestCase;

class _TestCase extends PostmanTestCase
{
    public int $userId = 2;

    public string $requestUrl = 'company/trade/sale';
}

// http/.../CreateTest.php
class CreateTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            method: 'POST',
            body: $this->body(quantity: 2),
        );
    }

    public function test_error___not_enough_stock(): void
    {
        $this->sendRequest(
            method: 'POST',
            body: $this->body(quantity: 3),
            assertStatus: 400,
        );
    }

    private function body(float $quantity): array
    {
        return [
            'stock_id' => 1,
            'products' => [
                [
                    'product_id' => 1,
                    'quantity' => $quantity,
                ],
            ],
        ];
    }
}

// modules/Trade/Tests/Sale/CreateTest.php (_TestCase: $userId, getValues(), createSale())
class CreateTest extends _TestCase
{
    protected function getValues(): array
    {
        return [
            ...parent::getValues(),
            'sales' => Sale::query()->count(),
        ];
    }

    public function test_success(): void
    {
        $this->assertChanges(
            callback: fn() => $this->createSale(quantity: 2, amount: 2000),
            expected: [
                'stock.batch_1.product_1' => [1, 0],
                'stock.batch_2.product_1' => [1.2, 0.2],
                'sales' => [2, 3],
            ],
        );
    }

    public function test_error___not_enough_stock(): void
    {
        $this->assertChanges(
            callback: fn() => $this->createSale(quantity: 3, amount: 3000),
            error: StockException::NOT_ENOUGH_PRODUCT,
        );
    }
}
```

## Don't
- Don't use `$this->get()/postJson()/call()`, `assertJson*`, `assertDatabaseHas` in feature tests — only `sendRequest()`.
- Don't extend `PostmanTestCase`/`DomainTestCase` directly in action tests — always the folder's `_TestCase`.
- Don't use factories, `RefreshDatabase`, `Http/Queue/Mail::fake()`, sqlite or manual transactions (`UploadedFile::fake()` only for uploads).
- Don't hardcode per-language arrays or SEO meta — `FormHelper`.
- Don't call `sendRequest()` twice with `path` in one test (URL accumulates).
- Don't assert HTTP responses or call controllers in domain tests — only `assertChanges()` over model/service calls.
