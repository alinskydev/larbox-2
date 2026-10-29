---
name: telegram-rules
description: Mandatory standards for Telegram messaging, covering transport in integrations/Telegram (Helpers/TelegramHelper, Jobs/TelegramJob), message templates in modules/*/Telegram/Templates, module mailing jobs, deploy/dev notices, and the http/Emulator/Telegram fake Bot API. Use when creating or editing code that sends Telegram messages or files, or when adding a Bot API method.
---

# Telegram Rules

Transport is the all-static `Integrations\Telegram\Helpers\TelegramHelper` plus the queued `Integrations\Telegram\Jobs\TelegramJob` (queue `telegram`), plain `Http` over the Bot API. Message content lives in plain classes with no base class: `modules/<Module>/Telegram/Templates/<Purpose>TelegramTemplate.php`.
Examples: `modules/Product/Telegram/Templates/ProductMailingTelegramTemplate.php` (used by `SendCompanyProductsByTelegramJob`), `ProductInformationTelegramTemplate.php` (sample, no callers yet), `app/Console/Commands/Larbox/DeployCommand.php` (`sendMessageToTelegram()`).

## Template rules
- Each template is `<Purpose>TelegramTemplate` with `public static function process(<typed args>): void` (`process(Product $product)`, `process(Company $company, array $products)`). Extra steps are further public static methods called via `static::` (`static::send($chats, $text, $files)`).
- Recipients:
  - Company chats: `Arr::pluck($company->credential->telegram_chats, 'chat_id')`. Source: `Modules\Company\Models\Credential` (table `company_credential`, `array` cast). Items are `{name, chat_id}`; max 5 and `chat_id` integer are validated in `http/{Admin,Company}/Company/Requests/CompanyRequest.php`, and `CredentialObserver::savingTelegramChats()` fills the template and casts `chat_id` to int.
  - Dev and deploy notices: `TelegramHelper::devChats()` (`TELEGRAM_BOT_DEV_CHAT_IDS`, comma-separated, trimmed).
- Text as an array: build `'*Label:* ' => $value` and pass it to `sendMessage(text: $data)` as is.
  - The helper escapes every value with `StringHelper::escapeForMarkdownV2()` (cast to string) and joins `key . ' ' . value` lines with `ArrayHelper::asMultilineString()`.
  - Keys are raw MarkdownV2: keep labels to `*bold*` text without unescaped `_ [ ] ( ) ~ > # + - = | { } . !`.
- Text as a string is sent unchanged: escape every dynamic part yourself with `StringHelper::escapeForMarkdownV2()`, as `DeployCommand::sendMessageToTelegram()` does (`"*$title* \\| $url"` plus a ```` ``` ```` block with `Str::limit($description, 2000)`).
- Values:
  - Localized JSON on models: `$model->name[$locale] ?? ''`, `$locale = app()->getLocale()` set once.
  - Amounts: `NumberHelper::formatAmountWithCurrency()` (default `UZS`).
- Files:
  - Generate them with an Excel writer (`$writer->process(); $writer->spreadsheet->save($path)`).
  - Save to `storage_path('larbox/_tmp/' . StringHelper::uuid())` with a display `filename` (`"Products - $date.xlsx"`).
  - Send with `sendGroupedFiles(chats:, files: [['path' => ..., 'filename' => ...], ...])`.
  - Don't `unlink()` them: the queued `TelegramJob` reads the file later, and `app:process_db_daily` removes `_tmp` files older than 1 day.
- Bulk mailings use a module job `modules/<Module>/Jobs/Send<...>ByTelegramJob extends App\Base\Job` (`SendCompanyProductsByTelegramJob`):
  - Constructor takes ids only (`int $companyId`), sets `$this->queue = 'telegram'`; `retryUntil()` returns `now()->addDay()`.
  - Data is loaded in private methods (`company()`, `products()` via `getQueryWithRelations()`).
  - `handle()` calls the template inside try/catch; the catch does `release(60 * 10)` + `Log::error()`.
  - A command dispatches one job per company with chats (`jsonb_array_length(company_credential.telegram_chats) > 0`): `app/Console/Commands/Examples/SendCompanyProductsByTelegramCommand.php` (currently disabled by an early `return`).

## Transport rules (integrations/Telegram)
- Public API, all `void` (except `devChats()`), never calling HTTP synchronously:
  - `sendMessage(array $chats, string|array $text)`
  - `sendFile(array $chats, string $file, string $type /* 'document'|'photo' */, string $caption = '', ?string $filename = null)` → `send<Type>`
  - `sendGroupedFiles(array $chats, array $files)` (`array{path, filename?}` items)
  - `devChats(): array`
- Each call dispatches `new TelegramJob(callback: new SerializableClosure(function () use (...) { Http::throw()->...->post(...); }))`:
  - One job per chat. `sendGroupedFiles` dispatches one `sendMediaGroup` job per chat per 10-file chunk (media `attach://file<n>`, multipart); a 1-file chunk falls back to `sendFile(type: 'document')`.
  - Capture only scalars and arrays (chat id, text, file path); `file_get_contents()` runs inside the closure.
- URL: `config('integrations.telegram_bot.url') . '/<method>'`. `sendMessage` and `sendFile` send `'parse_mode' => 'MarkdownV2'`; `sendFile` escapes the caption; `sendMediaGroup` sends empty captions without `parse_mode`.
- `TelegramJob`:
  - `retryUntil()` = 1 day.
  - In the catch: `isClientError($e) ? fail($e) : release(60 * 10)`, then `Log::error()`.
  - `isClientError()`: a `RequestException` with a 4xx other than 429 (chat not found, bot blocked, bad request). Everything else is retried.
- A new Bot API method needs all of these:
  - A static method in `TelegramHelper` following the closure + `TelegramJob` pattern.
  - A route `Route::post('<method>', [TelegramController::class, '<method>'])->name('<method>')` in `http/Emulator/Telegram/routes.php` (prefix `telegram`, camelCase method names: `sendMessage`, `sendDocument`, `sendPhoto`, `sendMediaGroup`).
  - `TelegramController::<method>(): JsonResponse` returning `$this->successResponse()`.
  - A `test_<snake_method>` in `http/Emulator/Telegram/Tests/TelegramTest.php` (`$this->sendRequest(method: 'POST', path: '<method>')`; `_TestCase` sets only `$requestUrl = 'emulator/telegram'`).
- Config `config/integrations.php`, key `telegram_bot`:
  - `url` = `TELEGRAM_BOT_URL`: `${APP_URL}/emulator/telegram` in non-production, `https://api.telegram.org/bot${TELEGRAM_BOT_KEY}` on prod (`.env.example`).
  - `dev_chat_ids` = `TELEGRAM_BOT_DEV_CHAT_IDS`.

## Template
```php
<?php

namespace Modules\<Module>\Telegram\Templates;

use App\Helpers\NumberHelper;
use Illuminate\Support\Arr;
use Integrations\Telegram\Helpers\TelegramHelper;
use Modules\<Module>\Models\<Entity>;

class <Purpose>TelegramTemplate
{
    public static function process(<Entity> $model): void
    {
        $locale = app()->getLocale();

        $data = [
            '*Name:* ' => $model->full_text,
            '*Category:* ' => $model->category->name[$locale] ?? '',
            '*Price:* ' => $model->price ? NumberHelper::formatAmountWithCurrency($model->price->price) : '',
        ];

        TelegramHelper::sendMessage(
            chats: Arr::pluck($model->company->credential->telegram_chats, 'chat_id'),
            text: $data,
        );
    }
}
```

## Don't
- Don't call `Http::` to Telegram outside `TelegramHelper`, create a transport job per message type, or add a Telegram SDK package (none is installed).
- Don't build message text in jobs, observers or controllers; use a template. The only inline text is the dev notice in `DeployCommand`.
- Don't hardcode the bot URL, token or chat ids.
- Don't use `parse_mode` `Markdown` or `HTML`, or `strip_tags()`; the transport is MarkdownV2 with escaping.
- Don't add webhook or incoming-update handling: none exists, and `TELEGRAM_BOT_SECRET_TOKEN` (`.env.example`) is not read by any config.
