---
name: database-rules
description: Mandatory standards for migrations (modules/*/Database/Migrations), seeders (modules/*/Database/Seeders), <module>-relations.php FK files, app/Database and app/Helpers/Database (partitioning, indexes, locks, seeder helpers), connections main/history/telescope and the larbox:migrate flow. Use when creating or editing tables, columns, indexes, partitions, seed data, foreign keys or deploy-time schema changes.
---

# Database Rules

Files: `modules/<Module>/Database/Migrations/<table>.php`, `modules/<Module>/Database/Seeders/<table>.php`, `modules/<Module>/Database/<module>-relations.php`. Canonical: `modules/Trade/Database/Migrations/trade_purchase.php`, `.../trade_sale_product.php` (partitioned), `modules/Product/Database/product-relations.php`, `modules/Trade/Database/Seeders/trade_purchase_product.php`.

## Flow & infrastructure
- No registry. `larbox:migrate` (`app/Console/Commands/Larbox/MigrateCommand.php`): flush `Cache::tags(['db_schema'])` → `migrate:fresh` over `modules/*/Database/Migrations/*.php` → `require` every `modules/*/Database/Seeders/*.php` (glob order: module, then file name; no FKs exist yet) and call `run()` → `migrate` over `modules/*/Database/*.php` (relations). Adding a file is enough.
- Migration name = file basename, so table names (= file names) are unique across modules. `database/migrations/` holds only framework tables (`job_active`, `job_batches`, `job_failed`, `telescope_*`).
- `db:seed`, `db:wipe`, `migrate*`, `larbox:migrate`, `larbox:test`, `larbox:export_postman_collection` are disabled outside `local` (`routes/console.php`). Production changes = run-once script from `larbox:create_file_for_deploy` → `app/Console/Commands/Deploy/Y_m_d__H_i_s.php` (`return new class { public function run(): void {} }`, pre-imports `Schema`, `Blueprint`, `DB`, `DatabaseSeederHelper`), run once by `larbox:deploy` inside one `safelyDBProcess()`, tracked in `system_event` (`type` = `EventTypeEnum::DEPLOY`, `additional->>'file'`).
- Connections (`config/database.php`, all pgsql via `App\Database\*` + `DatabaseServiceProvider`): `main` (default, `DB_MAIN_CONNECTION`), `history`, `telescope`. `PostgresSchemaBuilder::getColumnListing()` is cached 24h under tag `db_schema` (drives mass-assignment), flushed by `larbox:migrate` and `larbox:deploy` — new columns are not fillable until the flush.
- Locks: `DatabaseTransactionHelper::lock($key, $seconds = 60)` / `lockModel($model)` (`"<table>:<pk>"`; blocking cache lock, released after commit/rollback, re-entrant per key) — the only locking mechanism.
- Retention/vacuum chains: `app/Console/Commands/App/ProcessDBDailyCommand.php` (`RemoveOldDataJob`, `ProcessDBJob`); add an entry when creating a log-like table.

## Migrations
- `return new class() extends Migration` with `up()` and `down()` (keep the "Run/Reverse the migrations." `@return void` docblocks); `down()` = `Schema::dropIfExists('<table>')`. One table per file, never FKs.
- Column order: `id` → (`parent_id`) → `creator_id` → `company_id` → other `*_id` → `morphs()` → business columns → JSON → `created_at` → `updated_at` → `softDeletes()`; blank line, then composite `index`/`unique`/`primary`. Nested set: `parent_id` (nullable, no own index), `bigInteger('lft')`, `bigInteger('rgt')` after `company_id` + `$table->index(['parent_id', 'lft', 'rgt'])` (`product_category.php`).
- PK: `$table->id()`; 1:1 extension `bigInteger('<owner>_id')->primary()` (`user_profile`, `company_credential`); natural string key `string('name')->primary()` (`system_setting`, `user_auth_code.username`).
- References: `$table->bigInteger('x_id')->index()` (+ `->nullable()`), `company_id` indexed on partitioned tables too.
- Timestamps: `timestamp('created_at')->index()` + `timestamp('updated_at')`. Append-only/log tables: `created_at` only (`stock_batch`, `trade_recalculation_product`, `audit_record_action`, `audit_error_log`, `user_notification`, `user_auth_code`, `integration_token`, `system_event`, `history_store`); update-only `updated_at` (`product_price`); none: line tables (`trade_*_product`, `stock_product`), `seo_meta`, 1:1 and settings tables. `softDeletes()` last only if the model uses `ModelSoftDeleteTrait`.
- Types: money `decimal('price', 20)`, quantities `decimal('quantity', 20, 3)`; enums `enum('status', XEnum::values())` (import the enum); bounded strings `string('phone', 20)`, `string('code', 10)`, `string('description', 1000)`; long text `text()`; flags `boolean('is_x')->default(...)`; `tinyInteger`/`smallInteger`/`integer` for small counters/codes (`attempts_left`, `response_status`, `remaining`); encrypted values `text()` (`company_credential.store`, `integration_token.token`).
- JSON: `jsonb` for localized (`name`, `description`; one key per `config('larbox.languages')`) and data JSON (`params`, `routes`, `record_limits`, `product_snapshot`, `old_values`); `json` only for image lists/thumbs, `seo_meta.value`/`parsed_value`, `audit_error_log.trace`. Optional lists/maps get `->default('[]')` (`user_role.routes`, `system_event.additional`, `history_store.additional`).
- Images (external storage): `string('image')->nullable()` + `json('image_thumbs')->default('[]')`; multiple: `json('image_list')->default('[]')` + `json('image_list_thumbs')->default('[]')`. Local-storage images: one string column, no thumbs (`company.logo`, `system_language.image`).
- Slug `string('slug')->index()`; search text `string('full_text')`; polymorphic `$table->morphs('auditable')`; unique `->unique()` or composite `$table->unique([...])`.
- Pivot `_ref`: no id/timestamps; ref columns (non-leading ones `->index()`) + `$table->primary([...])` (`user_role_ref.php`, `user_company_responsibility_ref.php`).
- Indexes via `DatabaseMigrationIndexHelper` after the create (default connection only): `createLowerIndex(table, field, scopeField)` / `createLocalizedLowerIndex(...)` back case-insensitive `UniqueRule` (`whereLower`) checks (`product_brand`, `user`, `product_category`, `product_specification`, `product_option`); `createGinIndex(table, 'full_text')` / `createLocalizedGinIndex(table, 'name')` (`gin_trgm_ops`) back `LIKE`/`LOCALIZED_LIKE`/`SIMILARITY` filters (`product`, `product_option`).

### Partitioning
- High-volume company data (`product`, `product_option`, `product_option_ref`, `product_price`, `seo_meta`, `audit_record_action`, `stock_product`, `trade_purchase_product`, `trade_sale_product`, `trade_recalculation_product`): wrap `Schema::create($table, ...)` in `DatabaseMigrationPartitionHelper::createTableWithPartitionsByCompanyIdRanges(table:, fields: 'company_id', tableCallback: function ($table) {...})`.
- The helper drops the table, captures the generated SQL, rewrites the PK to include the partition fields (`PRIMARY KEY (id, company_id)`, throws if no PK) and creates ranges of 1000 company ids (1 range locally, 5 elsewhere; matches the 5000-company cap in `CompanyObserver::creating()`). Optional `hasDefaultPartition: true` (`<table>___default`), `connection:`.
- Unique constraints on partitioned tables start with the partition key: `$table->unique(['company_id', 'purchase_id', 'product_id'])`.
- Partitioned pivot: denormalize the parent's company as `<parent>_company_id`, partition by it, put it first in the PK (`product_option_ref.php`).
- `createTableWithPartitionsByMonths()` exists but is unused.

### History connection
- `Schema::connection('history')` in `up()`/`down()`, `dropIfExists` before `create` (`migrate:fresh` wipes only `main`); no FKs, no seeders (`history_store.php`).

## Seeders
- `return new class() extends Seeder` with `public function run()`; one `DB::table('<table>')->insert([...])` of literal rows. No models (observers must not fire), factories or Faker.
- Never set `id`; rows are referenced by hard-coded ids (users: 1 superadmin, 2 director of company 1, 3 director of company 2 "Company 1-1", 4 employee of company 1) — append rows, never reorder.
- Values: `date('Y-m-d H:i:s')`, `json_encode([...])`, `XEnum::CASE->value`, `Hash::make(...)`, `Crypt::encryptString(json_encode(...))` for `encrypted:array`, files `'test_data/images/1.png'`, localized `DatabaseSeederHelper::localized('Category 1')`, slugs `DatabaseSeederHelper::slug('Brand 1')` (`false` drops the locale suffix in both).
- Nested-set rows set `lft`/`rgt`/`parent_id` explicitly, starting with the holding root (`product_category.php`). Columns with DB defaults may be omitted (`*_thumbs`, `is_excel_import_running`).
- Group rows with `// Purchase 1 (pending)` comments framed by blank lines; private helpers and enum/config-driven rows are fine (`company_subscription.php` → `recordLimits()`, `system_language.php`).

## Relations (`<module>-relations.php`)
- `return new class() extends Migration` with `up()` only. Modules without FKs have no file (History, Integration, System).
- One `Schema::table('<table>', ...)` per table, tables alphabetical, FKs in column order.
- Always `->references('id')->on('<table>')->onUpdate('cascade')->onDelete(...)`; one line if it fits, otherwise one call per line followed by a blank line when more FKs follow.
- `restrict` by default: `creator_id` → `user`, `company_id` → `company`, parent/document refs (incl. `trade_purchase_product.purchase_id`). `cascade` only for `_ref` pivots, 1:1 extension tables (`user_profile`, `company_credential`) and `product_option.specification_id`.
- FK into a partitioned table must be composite: `$table->foreign(['product_company_id', 'product_id'])->references(['company_id', 'id'])->on('product')`. Without a `<parent>_company_id` column, declare none (`*.product_id` in `stock_product`, `product_price`, `trade_*_product`, `trade_recalculation_product`; `product.parent_id`, `product_option_ref.option_id`).

## Template
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('<table>', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('creator_id')->index();
            $table->bigInteger('company_id')->index();
            $table->string('name');
            $table->timestamp('created_at')->index();
            $table->timestamp('updated_at');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('<table>');
    }
};
```
Partitioned variant: see `trade_sale_product.php`. Relations file: same imports and `up()` docblock, body `Schema::table('<table>', function (Blueprint $table) { $table->foreign('creator_id')->references('id')->on('user')->onUpdate('cascade')->onDelete('restrict'); });`.

## Don't
- Put domain migrations/seeders in `database/`, use timestamped names, `make:migration` or plain `php artisan migrate`.
- Declare FKs in table migrations or add `down()` to relations files.
- Use `$table->timestamps()`, `foreignId()`, `constrained()`, `unsignedBigInteger()` — zero usages.
- Set explicit `id` in seeders, insert through models, or reorder existing seed rows.
- Create a plain FK to a partitioned table's `id`, or a unique index on a partitioned table without the partition key.
- Add FKs/relations entries for `history` tables.
- Change production schema/data outside a `larbox:create_file_for_deploy` script.
