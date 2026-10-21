<?php

namespace Http\Admin\Company\Tests\Company;

use App\Testing\Feature\Helpers\FormHelper;

class UpdateTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            method: 'PUT',
            path: '1',
            body: $this->body(),
        );
    }

    public function test_error___has_children(): void
    {
        $this->sendRequest(
            method: 'PUT',
            path: '1',
            body: [
                ...$this->body(),
                'parent_id' => 2,
            ],
            assertStatus: 422,
        );
    }

    private function body(): array
    {
        return [
            'parent_id' => null,
            'subscription_id' => 1,
            'name' => 'Company 1',
            ...FormHelper::deletableFiles(
                field: 'logo',
                files: FormHelper::files(),
                oldKeys: [0],
            ),
            'address' => 'Address 1',

            'director' => [
                'username' => 'director_1@local.host',
                'phone' => '998020000001',
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
