<?php

namespace Http\Admin\User\Tests\Role;

class UpdateTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            method: 'PUT',
            path: '2',
            body: [
                'name' => 'Director 1',
                'description' => 'Description 2-1',
                'routes' => [
                    'company.company.company.index',
                    'company.company.company.show',
                ],
                'is_confirmed' => true,
            ],
        );
    }

    public function test_error___not_updatable(): void
    {
        $this->sendRequest(
            method: 'PUT',
            path: '1',
            body: [
                'name' => 'Admin 1',
                'description' => 'Description 1',
                'routes' => [],
                'is_confirmed' => true,
            ],
            assertStatus: 400,
        );
    }
}
