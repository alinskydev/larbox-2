<?php

namespace Http\Admin\System\Tests\Setting;

use App\Testing\Feature\Helpers\FormHelper;

class UpdateTest extends _TestCase
{
    public function test_success(): void
    {
        $this->sendRequest(
            method: 'PUT',
            body: [
                'admin_email' => 'info@local.host',
                'favicon' => FormHelper::files(),
                'logo' => FormHelper::files(),
                'project_name' => 'Larbox 2',
            ],
        );
    }
}
