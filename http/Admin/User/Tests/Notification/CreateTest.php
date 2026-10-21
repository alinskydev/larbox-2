<?php

namespace Http\Admin\User\Tests\Notification;

use Modules\User\Enums\NotificationTypeEnum;

class CreateTest extends _TestCase
{
    public function test_non_deleted(): void
    {
        $this->sendRequest(
            method: 'POST',
            body: $this->body(),
        );
    }

    public function test_deleted(): void
    {
        $this->sendRequest(
            method: 'POST',
            body: [
                ...$this->body(),
                'show' => [
                    'only_deleted',
                ],
            ],
        );
    }

    private function body(): array
    {
        return [
            'type' => NotificationTypeEnum::MESSAGE->value,
            'text' => 'Text 3',
            'filter' => [
                'company_id' => '1',
            ],
        ];
    }
}
