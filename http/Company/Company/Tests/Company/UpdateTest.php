<?php

namespace Http\Company\Company\Tests\Company;

use App\Testing\Feature\Helpers\FormHelper;

class UpdateTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            method: 'PUT',
            path: '1',
            body: [
                'name' => 'Company 1',
                ...FormHelper::deletableFiles(
                    field: 'logo',
                    files: FormHelper::files(),
                    oldKeys: [0],
                ),
                'address' => 'Address 1',
                'telegram_chats' => [
                    [
                        'name' => 'Full name 1',
                        'chat_id' => 1,
                    ],
                    [
                        'name' => 'Full name 2',
                        'chat_id' => 2,
                    ],
                ],
            ],
        );
    }
}
