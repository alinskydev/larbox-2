<?php

namespace Http\Common\User\Tests\Notification;

use App\Helpers\StringHelper;
use Modules\User\Enums\NotificationTypeEnum;
use Modules\User\Models\Notification;

class DownloadTest extends _TestCase
{
    public function test_success(): void
    {
        $path = storage_path('larbox/_tmp');
        $name = StringHelper::uuid() . '.xlsx';

        file_put_contents("$path/$name", '');

        Notification::query()->where('id', 1)->update([
            'type' => NotificationTypeEnum::EXCEL_EXPORT_COMPLETED->value,
            'params' => ['file' => $name],
        ]);

        $this->sendRequest(
            path: '1/download',
        );

        unlink("$path/$name");
    }

    public function test_error___not_found(): void
    {
        $this->sendRequest(
            path: '1/download',
            assertStatus: 404,
        );
    }
}
