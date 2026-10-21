---
name: mail-rules
description: Mandatory standards for e-mail in app/Mail (Mail, MailHelper, SendMailJob) and modules/*/Mail (mail classes and their per-locale views next to the class). Use when creating or editing a mail class, a mail template, or code that sends e-mail.
---

# Mail Rules

Mail classes live in `modules/<Module>/Mail/<Purpose>Mail.php` (namespace `Modules\<Module>\Mail`) and extend `App\Mail\Mail`, which extends `Illuminate\Mail\Mailable` and already uses `Queueable` and `SerializesModels`. Mail is always sent with `App\Mail\MailHelper::send()`, which queues `App\Mail\SendMailJob`.
The only example: `modules/User/Mail/AuthCodeMail.php` + `modules/User/Mail/auth_code/{en,ru,uz}.php`, sent from `modules/User/Observers/AuthCodeObserver.php`.

## Rules
- Name the class `<Purpose>Mail extends Mail` (`use App\Mail\Mail;`). The file name equals the class name.
- Mail data goes in promoted `public` constructor properties (`public string $code`). Public properties become view variables. Models are fine (`SerializesModels`).
- The class has one method, `build(): static`, which does `return $this->subject(__('<Russian text>'))->view('<view_name>');`.
- `App\Mail\Mail::view()` resolves the view name:
  - It registers the mail class directory as namespace `mail_<md5>` and renders `<view_name>.<locale>`, which is `modules/<Module>/Mail/<view_name>/<locale>.php`.
  - Pass only the snake_case folder name. No path, no locale, no `views/` subfolder.
- Views: a `<view_name>/` folder next to the class holds one plain PHP file per locale (`en.php`, `ru.php`, `uz.php`). Create all of them even when the text is the same.
  - Header: `<?php /** @var string $code */ ?>`.
  - Body: plain content with `<?= $code ?>`.
- Locale is the app locale at dispatch time. `App\Base\Job::__construct()` stores `app()->getLocale()`, and `SendMailJob` sends with `->locale($this->locale)`.
- Send from the code that decides (observer or service), with named args: `MailHelper::send(mail: new AuthCodeMail($model->code), to: $model->username);`. `to` is `string|array`.
- `SendMailJob`:
  - Runs on queue `mail`, with `retryUntil()` set to 1 day.
  - Calls `MailFacade::to($this->to)->locale($this->locale)->send($this->mail)`.
  - On any error it calls `release(60 * 10)` and `Log::error()`.
  - Don't add retry or error handling anywhere else.
- Transport comes from `config/mail.php` and `MAIL_*` env. Tests run with `MAIL_MAILER=array` and `QUEUE_CONNECTION=sync` (`phpunit.xml`).

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

View `modules/<Module>/Mail/<purpose>/ru.php` (same for `en.php` and `uz.php`):
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
- Don't call `Mail::to()->send()`, `->queue()` or `->later()` from module code. Use `MailHelper::send()`.
- Don't add `implements ShouldQueue`, `use Queueable` or `use SerializesModels` to a mail class. The base already has them.
- Don't use the `envelope()`, `content()` or `attachments()` API, Markdown mails, Blade templates, `resources/views` or `mail::` components (none are used).
- Don't put a locale or path in `view()`, a `views/` subfolder, or a module directory named `Mails/`.
- Don't put business logic (whether or when to send) inside the mail class.
