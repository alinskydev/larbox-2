<?php

namespace Http\Admin\Company\Tests\Company;

class UpdateCredentialTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            method: 'PATCH',
            path: '1/update-credential',
            body: [
                'credential' => [
                    'store' => [
                        'username' => 'Username 1',
                        'new_password' => 'Password 1',
                    ],
                ],
            ],
        );
    }
}
