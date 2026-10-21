<?php

namespace Http\Common\Record\Tests\Index;

use App\Testing\Feature\PostmanTestCase;
use Modules\User\Enums\UserTypeEnum;

class _TestCase extends PostmanTestCase
{
    public string $requestUrl = 'common/record';

    public function test_type___company(): void
    {
        $this->sendRequest(
            path: 'company',
            query: [
                'filter' => [
                    'id' => [1, 2],
                    'value' => 'company',
                    'is_root' => 1,
                ],
            ],
        );
    }

    public function test_type___user(): void
    {
        $this->sendRequest(
            path: 'user',
            query: [
                'filter' => [
                    'id' => [4, 5],
                    'value' => 'name',
                    'company_id' => 1,
                    'type' => UserTypeEnum::COMPANY_EMPLOYEE->value,
                ],
            ],
        );
    }

    public function test_type___user_role(): void
    {
        $this->sendRequest(
            path: 'user_role',
            query: [
                'filter' => [
                    'id' => [1, 2],
                    'value' => 'director 1',
                    'type' => 'director',
                ],
            ],
        );
    }

    public function test_common___show_with_deleted(): void
    {
        $this->sendRequest(
            path: 'company',
            query: ['show' => ['with_deleted']],
        );
    }

    public function test_common___show_only_deleted(): void
    {
        $this->sendRequest(
            path: 'company',
            query: ['show' => ['only_deleted']],
        );
    }

    public function test_common___pagination(): void
    {
        $this->sendRequest(
            path: 'company',
            query: [
                'page_size' => 50,
                'page' => 1,
            ],
        );
    }
}
