<?php

namespace App\Excel\Import;

use App\Base\Job;
use App\Base\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Modules\User\Enums\NotificationTypeEnum;
use Modules\User\Helpers\NotificationHelper;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ExcelImportJob extends Job
{
    /**
     * @param class-string<Model> $modelClass
     */
    public function __construct(
        private string $modelClass,
        private array $records,
        private string $recordLabel,
        private bool $isLast,
    ) {
        parent::__construct();
        $this->queue = 'excel';
    }

    public function handle(): void
    {
        try {
            $failedRecords = [];

            $ids = Arr::pluck($this->records, 'id');
            $ids = array_filter($ids);

            /** @var Collection<Model> */
            $models = $this->modelClass::query()->whereIn('id', $ids)->withTrashed()->get()->keyBy('id');

            foreach ($this->records as $record) {
                try {
                    $data = Arr::except($record, ['id']);
                    $model = $record['id'] ? $models->get($record['id']) : new $this->modelClass();

                    if ($model === null) {
                        throw new \Exception(__('Запись ":name" не найдена', [
                            'name' => $record[$this->recordLabel],
                        ]));
                    }

                    $model->safelySave($data);
                } catch (\Throwable $e) {
                    $field = $record[$this->recordLabel];
                    $failedRecords[$field] = $e->getMessage();
                }
            }

            if ($failedRecords) {
                NotificationHelper::insertMultiple(
                    recipientIds: [$this->userId],
                    type: NotificationTypeEnum::EXCEL_IMPORT_FAILED,
                    params: [
                        'records' => $failedRecords,
                    ],
                );
            }
        } catch (\Throwable $e) {
            $this->fail($e);
            Log::error($e->getMessage(), ['exception' => $e]);

            NotificationHelper::insertMultiple(
                recipientIds: [$this->userId],
                type: NotificationTypeEnum::EXCEL_IMPORT_FAILED,
                params: [
                    'message' => __('Не удалось импортировать файл'),
                ],
            );
        } finally {
            if ($this->isLast) {
                user()->company->updateQuietly(['is_excel_import_running' => false]);

                NotificationHelper::insertMultiple(
                    recipientIds: [$this->userId],
                    type: NotificationTypeEnum::EXCEL_IMPORT_COMPLETED,
                );
            }
        }
    }
}
