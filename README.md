# Larbox 2

A starter kit for building B2B SaaS platforms on Laravel 13 (API-only). Out of the box it provides a modular multi-tenant REST API (data is isolated per company, with support for holdings and subsidiaries) and a wide range of ready-made features: roles and permissions, products, warehouses and stock balances, purchases and sales, audit and history, Excel/PDF, mail, Telegram, and external integrations.

All code follows a single standard and a consistent style: every layer (models, requests, controllers, services, tests, etc.) has its own template and rules in `.claude/skills`. This makes the project well suited to AI code generation: an agent relies on existing examples and rules and writes new code in the same style as the existing code.

| Stack | |
|---|---|
| PHP | `^8.3`; extensions `pdo_pgsql`, `redis` (phpredis), `gd`, `zip`, `intl`, `mbstring`, `fileinfo` |
| Framework | Laravel `^13.7` + custom Larbox abstractions in `app` |
| Database | PostgreSQL only: connections `main`, `history`, `telescope`; `main` requires the `pg_trgm` extension |
| Cache | Redis — required (cache tags, locks) |
| Authentication | Laravel Sanctum (Bearer tokens) |
| Languages | `en`, `ru`, `uz` (`Accept-Language`), configurable in `config/larbox.php -> languages` |

## Installation

##### Do not use `composer setup`.

1. Clone the repository and run `composer install`.
2. Create `.env` from [.env.example](.env.example) and set the required parameters. Run `php artisan key:generate`.
3. Create the 3 required databases: `main`, `history`, `telescope`. In the main database, run `CREATE EXTENSION IF NOT EXISTS pg_trgm;`.
4. Create the `storage/larbox/_tmp` directory.
5. Run `php artisan larbox:migrate`.

## Environment

- Do not change `APP_NAME` or `APP_KEY` on a live server: deploy script tracking depends on the former, field encryption and queued job signing on the latter.
- Locally, `STORE_*`, `STORAGE_*` and `TELEGRAM_BOT_URL` point to the emulators at `${APP_URL}/emulator/*`.

## Commands

| Command | Purpose |
| --- | --- |
| Use `php artisan larbox:migrate` instead of `php artisan migrate:fresh` | `migrate:fresh` over `modules/*/Database/Migrations` → seeders → FKs from `<module>-relations.php`. **Wipes the database** |
| Use `php artisan larbox:test` instead of `php artisan test` | `optimize:clear` + `larbox:migrate` + tests. **Wipes the database** |
| `php artisan larbox:collect_translations` | Collects `__('...')` strings into `lang/*.json` and field names into `lang/*/fields.php`. Existing entries are not overwritten |
| `php artisan larbox:create_file_for_deploy` | Creates a script file in `app/Console/Commands/Deploy/`, e.g. for a database migration or a data recalculation. The scripts run automatically on the server when `php artisan larbox:deploy` is executed |
| `php artisan larbox:deploy` | Deploy: pending deploy scripts → cache flush → `optimize` → `queue:restart` → Telegram notification (if a dev chat is set in `.env`). It is best added to a CI/CD platform (e.g. Jenkins) so that it runs automatically whenever the server receives an update |

- **Production changes** (data, schema of an existing database) — only via deploy scripts: `migrate*` is blocked there.
- **Translations**: source strings are in Russian, e.g. `__('Запись не найдена')` ("Record not found"); the collector does not pick up business exception messages, so add them to `lang/*.json` manually.

## Architecture

| Namespace | Directory | Contents |
|---|---|---|
| `App\` | `app/` | Larbox core and a few default Laravel classes |
| `Modules\<Module>\` | `modules/` | Domain: Audit, Company, History, Integration, Product, Seo, Stock, System, Trade, User |
| `Http\<Area>\<Module>\` | `http/` | HTTP layer by area `Admin`, `Company`, `Common`, `Emulator`: `routes.php`, `Controllers/`, `Requests/`, `Filters/`, `Tests/` |
| `Integrations\<Name>\` | `integrations/` | External service clients. Examples include a fictional Store and a real Telegram implementation |

### Modules

| Directory | Purpose |
|---|---|
| `Builders/` | Custom query builders with reusable model scopes |
| `Database/` | `Migrations/` — table migrations, one file per table, no timestamps, applied only via `larbox:migrate`; `Seeders/` — seed data, one file per table; `<module>-relations.php` — foreign keys of the module's tables, run after seeders |
| `Enums/` | Enums for stored values; published to the frontend via the system enums endpoint |
| `Excel/` | Excel export writers and import logic |
| `Exceptions/` | Business exceptions with error constants; rendered as 400 `{"message": ...}` |
| `Helpers/` | Static utility classes of the module |
| `Jobs/` | Queued jobs |
| `Mail/` | Mail classes with per-locale views |
| `Middleware/` | Module-specific HTTP middleware |
| `Models/` | Eloquent models: relations, casts, auto filters by company, observer list |
| `Observers/` | Defaults, checks and side effects on model save/delete |
| `PDF/` | PHP views for PDF documents |
| `Requests/` | Form requests reused outside a single HTTP area |
| `Resources/` | JSON response shape of a model |
| `Search/` | List queries: allowed filters, sorts, `with[]` relations and `show[]` options |
| `Services/` | Domain logic and processes (sale, purchase, stock movement) in transactions |
| `Singletons/` | Data loaded once per request (e.g. system settings, languages) |
| `Telegram/` | Telegram message templates |
| `Tests/<Entity>/` | Domain tests of services, independent of HTTP |
| `Traits/` | Shared traits mixed into the module's classes |

- A module contains only the directories it needs.

### Http

| Prefix | Audience |
|---|---|
| `admin/` | Administrators; History, System, User and `update-credential` — superadmin only |
| `company/` | Company directors and employees |
| `common/` | Login, profile, notifications, system data |
| `emulator/` | Fake Storage, Store, Telegram |

- New http module will be registered automatically: just create `http/<Area>/<Module>/routes.php`.
- `apiResource`: `index`, `create`, `show`, `update`, `delete`, `restore`.
- `routes.php` sets a local name (`->name('add-products')`), and the prefix is derived from the URL: `company.trade.purchase.add-products`. **The full route name is the permission**, so every route must be named.

## Related files

- [AGENTS.md](AGENTS.md) — project reference and coding rules.
- `.claude/skills/<name>/SKILL.md` — mandatory per-layer standards (models, requests, controllers, tests, etc.).
