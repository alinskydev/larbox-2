---
name: telegram-rules
description: Mandatory standards for Telegram messaging, covering transport in integrations/Telegram (Helpers/TelegramHelper, Jobs/TelegramJob), message templates in modules/*/Telegram/Templates, module mailing jobs, deploy/dev notices, and the http/Emulator/Telegram fake Bot API. Use when creating or editing code that sends Telegram messages or files, or when adding a Bot API method.
---

# Telegram Rules

Transport is the all-static `Integrations\Telegram\Helpers\TelegramHelper` plus the queued `Integrations\Telegram\Jobs\TelegramJob` (queue `telegram`). Message content lives in plain classes with no base class: `modules/<Module>/Telegram/Templates/<Purpose>TelegramTemplate.php`.
Examples: `modules/Product/Telegram/Templates/ProductInformationTelegramTemplate.php`, `ProductMailingTelegramTemplate.php`, `app/Console/Commands/Larbox/DeployCommand.php` (`sendMessageToTelegram()`).

## Template rules
- Each template is `<Purpose>TelegramTemplate` with `public static function process(<typed args>): void` (`process(Product $product)`, `process(Company $company, array $products)`). Extra steps are further public static methods called via `static::` (`static::send($chats, $text, $files)`).
- Recipients:
  - Company chats: `Arr::pluck($company->credential->telegram_chats, 'chat_id')`. Source: `Modules\Company\Models\Credential`, table `company_credential`. Items are `{name, chat_id}`, max 5, normalized by `CredentialObserver`.
  - Dev and deploy notices: `TelegramHelper::devChats()` (`TELEGRAM_BOT_DEV_CHAT_IDS`, comma-separated).
- Text as an array: build `'*Label:* ' => $value` and pass it to `sendMessage(text: $data)` as is.
  - The helper escapes every value with `StringHelper::escapeForMarkdownV2()` and joins the lines with `ArrayHelper::asMultilineString()`.
  - Keys are raw MarkdownV2. Keep labels to `*bold*` text without unescaped `. - ( ) ! # + = |`.
- Text as a string is sent unchanged. Escape every dynamic part yourself with `StringHelper::escapeForMarkdownV2()`, as `DeployCommand::sendMessageToTelegram()` does (`"*$title* \\| $url"` plus a code block).
- Values:
  - Localized JSON: `$model->name[$locale] ?? ''`, with `$locale = app()->getLocale()` set once.
  - Amounts: `NumberHelper::formatAmountWithCurrency()`.
- Files:
  - Generate them with an Excel writer (`$writer->process(); $writer->spreadsheet->save($path)`).
  - Save to `storage_path('larbox/_tmp/' . StringHelper::uuid())` with a display `filename` (`"Products - $date.xlsx"`).
  - Send with `sendGroupedFiles(chats:, files: [['path' => ..., 'filename' => ...], ...])`.
  - Don't `unlink()` them. The queued `TelegramJob` reads the file later, and `app:process_db_daily` removes `_tmp` files older than 1 day.
- Bulk mailings use a module job `modules/<Module>/Jobs/Send<...>ByTelegramJob extends App\Base\Job` (`SendCompanyProductsByTelegramJob`):
  - Queue `telegram`, with `retryUntil()` returning `now()->addDay()`.
  - Data is loaded in private methods.
  - `handle()` calls the template inside try/catch, and the catch does `release(60 * 10)` and `Log::error()`.
  - A command dispatches one job per company (`app/Console/Commands/Examples/SendCompanyProductsByTelegramCommand.php`).

## Transport rules (integrations/Telegram)
- Public API, all returning `void` and never calling HTTP synchronously:
  - `sendMessage(chats:, text: string|array)`
  - `sendFile(chats:, file: <path>, type: 'document'|'photo', caption: '', filename: null)`
  - `sendGroupedFiles(chats:, files:)`
  - `devChats(): array`
- Each call dispatches `new TelegramJob(callback: new SerializableClosure(function () use (...) { Http::throw()->...->post(...); }))`:
  - One job per chat. `sendGroupedFiles` dispatches one job per chat per 10-file chunk (`sendMediaGroup`), and a 1-file chunk falls back to `sendFile`.
  - Capture only scalars and arrays (chat id, text, file path). `file_get_contents()` runs inside the closure.
- URL: `config('integrations.telegram_bot.url') . '/<method>'`. Messages and captions use `'parse_mode' => 'MarkdownV2'`, and the helper escapes captions.
- `TelegramJob`:
  - `retryUntil()` is 1 day.
  - In the catch it calls `Log::error()`.
  - A 4xx client error other than 429 (`isClientError()`: chat not found, bot blocked, bad request) leads to `fail()`. Any other error leads to `release(60 * 10)`.
- A new Bot API method needs all of these:
  - A static method in `TelegramHelper`.
  - A route `Route::post('<method>', [TelegramController::class, '<method>'])->name('<method>')` in `http/Emulator/Telegram/routes.php`.
  - `TelegramController::<method>(): JsonResponse` returning `$this->successResponse()`.
  - A `test_<snake_method>` in `http/Emulator/Telegram/Tests/TelegramTest.php`.
- Config: `config/integrations.php`, key `telegram_bot`:
  - `url` reads `TELEGRAM_BOT_URL`, which is `${APP_URL}/emulator/telegram` in non-production and `https://api.telegram.org/bot${TELEGRAM_BOT_KEY}` on prod (`.env.example`).
  - `dev_chat_ids` reads `TELEGRAM_BOT_DEV_CHAT_IDS`.

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
            '*Name:* ' => $model->name,
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
- Don't call `Http::` to Telegram outside `TelegramHelper`, create a job class per message type, or add a Telegram SDK package (none is installed).
- Don't build message text in jobs, observers or controllers. Use a template. The only inline text is the dev notice in `DeployCommand`.
- Don't hardcode the bot URL, token or chat ids.
- Don't use `parse_mode` `Markdown` or `HTML`, or `strip_tags()`. The transport is MarkdownV2 with escaping.
- Don't add webhook or incoming-update handling. None exists, and `TELEGRAM_BOT_SECRET_TOKEN` is not read by any config.
