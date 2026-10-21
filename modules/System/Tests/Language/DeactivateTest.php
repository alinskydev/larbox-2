<?php

namespace Modules\System\Tests\Language;

use Modules\System\Exceptions\LanguageException;
use Modules\System\Models\Language;

class DeactivateTest extends _TestCase
{
    public function test_success(): void
    {
        $this->assertChanges(
            callback: fn() => $this->deactivate('uz'),
            expected: [
                'language.uz.is_active' => [true, false],
            ],
        );
    }

    public function test_error___current_language_deactivation(): void
    {
        app()->setLocale('uz');

        $this->assertChanges(
            callback: fn() => $this->deactivate('uz'),
            error: LanguageException::CURRENT_LANGUAGE_DEACTIVATION,
        );
    }

    public function test_error___main_language_deactivation(): void
    {
        $this->assertChanges(
            callback: fn() => $this->deactivate('en'),
            error: LanguageException::MAIN_LANGUAGE_DEACTIVATION,
        );
    }

    private function deactivate(string $code): void
    {
        Language::query()->where('code', $code)->firstOrFail()->safelySave(['is_active' => false]);
    }
}
