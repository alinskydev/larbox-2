<?php

namespace Http\Admin\Company\Tests\Company;

use App\Testing\Feature\Helpers\FormHelper;

class CreateTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            method: 'POST',
            body: $this->body(),
        );
    }

    public function test_error___parent_must_be_at_the_top_level(): void
    {
        $this->sendRequest(
            method: 'POST',
            body: [
                ...$this->body(),
                'parent_id' => 3,
            ],
            assertStatus: 422,
        );
    }

    private function body(): array
    {
        return [
            'parent_id' => null,
            'subscription_id' => 1,
            'name' => 'Company 2',
            ...FormHelper::deletableFiles(
                field: 'logo',
                files: FormHelper::files(),
                oldKeys: [],
            ),
            'address' => 'Address 2',

            'director' => [
                'username' => 'director_2@local.host',
                'phone' => '998020000002',
                'password' => 'User_1234',
                'password_confirmation' => 'User_1234',

                'roles' => [2, 3],
            ],

            'credential' => [
                'telegram_chats' => array_map(
                    fn($index) => [
                        'name' => "Name $index",
                        'chat_id' => $index,
                    ],
                    range(1, 2),
                ),
            ],

            'is_confirmed' => true,
        ];
    }
}
