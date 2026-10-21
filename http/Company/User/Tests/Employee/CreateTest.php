<?php

namespace Http\Company\User\Tests\Employee;

class CreateTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            method: 'POST',
            body: $this->body(),
        );
    }

    public function test_error___parent_company_is_not_allowed(): void
    {
        $this->authAsUser(3);

        $this->sendRequest(
            method: 'POST',
            body: [
                ...$this->body(),
                'username' => 'employee_3_2@local.host',
            ],
            assertStatus: 422,
        );
    }

    private function body(): array
    {
        return [
            'company_id' => 1,
            'username' => 'employee_1_3@local.host',
            'phone' => '998040000005',
            'password' => 'User_1234',
            'password_confirmation' => 'User_1234',

            'profile' => [
                'first_name' => 'First name',
                'last_name' => 'Last name',
                'position' => 'Position',
            ],

            'roles' => [4, 5],
        ];
    }
}
