<?php

namespace App\Excel\Export;

use App\Base\Job;
use App\Helpers\StringHelper;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Modules\User\Enums\NotificationTypeEnum;
use Modules\User\Helpers\NotificationHelper;
use Symfony\Component\HttpFoundation\StreamedResponse;

abstract class ExcelExportJob extends Job
{
    public static int $instantLimit;
    public static int $totalLimit;

    public function __construct(
        protected string $sql,
        protected array $bindings,
    ) {
        parent::__construct();
        $this->queue = 'excel';
    }

    abstract public function createWriter(): ExcelExportWriter;

    public function handle(): void
    {
        try {
            $path = storage_path('larbox/_tmp');
            $name = StringHelper::uuid() . '.xlsx';

            File::makeDirectory($path, 0755, true, true);

            $writer = $this->createWriter();
            $writer->process();
            $writer->spreadsheet->save("$path/$name");

            NotificationHelper::insertMultiple(
                recipientIds: [$this->userId],
                type: NotificationTypeEnum::EXCEL_EXPORT_COMPLETED,
                params: [
                    'file' => $name,
                ],
            );
        } catch (\Throwable $e) {
            $this->fail($e);
            Log::error($e->getMessage(), ['exception' => $e]);

            NotificationHelper::insertMultiple(
                recipientIds: [$this->userId],
                type: NotificationTypeEnum::EXCEL_EXPORT_FAILED,
                params: [
                    'message' => __('Не удалось экспортировать файл'),
                ],
            );
        }
    }

    public function handleSync(): StreamedResponse
    {
        return $this->createWriter()->processAsStream();
    }
}
