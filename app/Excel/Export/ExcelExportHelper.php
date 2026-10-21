<?php

namespace App\Excel\Export;

use App\Base\EloquentBuilder;
use Symfony\Component\HttpFoundation\Response;

class ExcelExportHelper
{
    /**
     * @param class-string<ExcelExportJob> $jobClass
     */
    public static function process(string $jobClass, EloquentBuilder $query): Response
    {
        $count = $query->count();

        if ($count > $jobClass::$totalLimit) {
            throw new \Exception(__('Нельзя экспортировать более :limit записей', ['limit' => $jobClass::$totalLimit]));
        }

        $idsQuery = $query->select('id')->getQuery();

        $job = new $jobClass(
            sql: $idsQuery->toSql(),
            bindings: $idsQuery->getConnection()->prepareBindings($idsQuery->getBindings()),
        );

        if ($count <= $jobClass::$instantLimit) {
            return $job->handleSync();
        } else {
            dispatch($job);
            abort(202, __('Вы получите уведомление, как только файл будет готов'));
        }
    }

    public static function sanitizeRow(mixed $data): mixed
    {
        if (is_array($data)) {
            return array_map(fn($value) => static::sanitizeRow($value), $data);
        }

        return is_string($data) && !is_numeric($data) ? preg_replace('/^[=+\-@\s\x09\x0D]+/', '', $data) : $data;
    }
}
