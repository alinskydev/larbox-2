---
name: mail-rules
description: Mandatory standards for e-mail in app/Mail (Mail, MailHelper, SendMailJob) and modules/*/Mail (mail classes and their per-locale views next to the class). Use when creating or editing a mail class, a mail template, or code that sends e-mail.
---

# Mail Rules

Mail classes live in `modules/<Module>/Mail/<Purpose>Mail.php` (namespace `Modules\<Module>\Mail`) and extend `App\Mail\Mail` (`Illuminate\Mail\Mailable` + `Queueable` + `SerializesModels`). Mail is always sent with `App\Mail\MailHelper::send()`, which dispatches `App\Mail\SendMailJob`.
The only example: `modules/User/Mail/AuthCodeMail.php` + `modules/User/Mail/auth_code/{en,ru,uz}.php`, sent from `modules/User/Observers/AuthCodeObserver::created()`.

## Rules
- Name the class `<Purpose>Mail extends Mail` (`use App\Mail\Mail;`); file name = class name.
- Mail data goes in promoted `public` constructor properties (`public string $code`); public properties become view variables. Models are fine (`SerializesModels`), but prefer scalars.
- The class has one method, `build(): static`: `return $this->subject(__('<Russian text>'))->view('<view_name>');`. `build()` runs at send time inside the job, so `__()` uses the job locale.
- `App\Mail\Mail::view($view, array $data = []): static` resolves the view:
  - It registers the mail class directory as namespace `mail_<md5("<class>|<view>")>` (`View::replaceNamespace`) and renders `<view_name>.<locale>`, i.e. `modules/<Module>/Mail/<view_name>/<locale>.php`.
  - Pass only the snake_case folder name: no path, no locale, no `views/` subfolder.
- Views: a `<view_name>/` folder next to the class with one plain PHP file per locale (`en.php`, `ru.php`, `uz.php`); create all of them, translated (the current `auth_code/en.php` and `uz.php` still hold the Russian text).
  - Header: `<?php /** @var string $code */ ?>`.
  - Body: plain content with `<?= $code ?>`.
- Locale is the app locale at dispatch time: `App\Base\Job::__construct()` stores `app()->getLocale()`, the job middleware restores it, and `SendMailJob` sends with `->locale($this->locale)`.
- Send from the code that decides (observer or service), with named args: `MailHelper::send(mail: new AuthCodeMail($model->code), to: $model->username);`. Signature `send(Mail $mail, string|array $to): void`.
- `SendMailJob`:
  - Queue `mail`, `retryUntil()` = `now()->addDay()` (the base `Job` default is a year).
  - `handle()`: `MailFacade::to($this->to)->locale($this->locale)->send($this->mail)`.
  - On any `\Throwable`: `release(60 * 10)` + `Log::error()`.
  - Don't add retry or error handling anywhere else.
- Transport: `config/mail.php` + `MAIL_*` env (`.env.example`: smtp). Tests run with `MAIL_MAILER=array` and `QUEUE_CONNECTION=sync` (`phpunit.xml.dist`), so the job runs inline.

## Template
```php
<?php

namespace Modules\<Module>\Mail;

use App\Mail\Mail;

class <Purpose>Mail extends Mail
{
    public function __construct(
        public string $code,
    ) {}

    public function build(): static
    {
        return $this->subject(__('Код подтверждения'))->view('<purpose>');
    }
}
```

View `modules/<Module>/Mail/<purpose>/ru.php` (plus `en.php` and `uz.php`):
```php
<?php
/**
 * @var string $code
 */
?>

Ваш код для восстановления пароля: <?= $code ?>
```

Sending:
```php
MailHelper::send(
    mail: new <Purpose>Mail($model->code),
    to: $model->username,
);
```

## Don't
- Don't call `Mail::to()->send()`, `->queue()` or `->later()` from module code; use `MailHelper::send()`.
- Don't add `implements ShouldQueue`, `use Queueable` or `use SerializesModels` to a mail class; the base has them.
- Don't use the `envelope()`, `content()` or `attachments()` API, Markdown mails, Blade, `resources/views` or `mail::` components (none are used).
- Don't put a locale or path in `view()`, add a `views/` subfolder, or name the module directory `Mails/`.
- Don't put business logic (whether or when to send) inside the mail class.
