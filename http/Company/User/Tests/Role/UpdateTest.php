<?php

namespace Http\Company\User\Tests\Role;

class UpdateTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            method: 'PUT',
            path: '4',
            body: [
                'name' => 'Employee 1',
                'description' => 'Description 3-1',
                'routes' => [
                    'company.company.company.index',
                    'company.company.company.show',
                ],
                'is_confirmed' => true,
            ],
        );
    }
}
