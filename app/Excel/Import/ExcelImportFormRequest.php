<?php

namespace App\Excel\Import;

use App\Helpers\StringHelper;
use App\Requests\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use ZipArchive;

class ExcelImportFormRequest extends FormRequest
{
    private const UNCOMPRESSED_SIZE_LIMIT = 1024 * 1024 * 100;

    protected array $excelSheetInfo;

    protected array $excelColumns;
    protected array $excelColumnIndexes;

    public array $fileContent = [];

    public function __construct()
    {
        parent::__construct();

        if (user()->is_excel_import_running) {
            throw new \Exception(__('У вас есть активный процесс по импорту'));
        }
    }

    protected function checkAndPrepareExcel(string $lastColumn, int $rowLimit): bool
    {
        if ($this->getUncompressedSize() > self::UNCOMPRESSED_SIZE_LIMIT) {
            $this->validator->errors()->add('file', __('Файл повреждён или слишком большой'));
            return false;
        }

        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);

        $this->excelSheetInfo = $reader->listWorksheetInfo($this->file->path())[0];

        if ($this->excelSheetInfo['totalRows'] - 1 > $rowLimit) {
            $this->validator->errors()->add('file', __('Нельзя импортировать более :limit записей', [
                'limit' => $rowLimit,
            ]));

            return false;
        }

        if ($this->excelSheetInfo['lastColumnLetter'] !== $lastColumn) {
            $this->validator->errors()->add('file', __('Количество колонок отличается'));
            return false;
        }

        $sheet = $reader->load($this->file->path())->getSheet(0);

        $this->fileContent = StringHelper::escapeTags($sheet->toArray());
        Arr::forget($this->fileContent, 0);

        $this->excelColumnIndexes = array_flip($this->excelColumns);

        return true;
    }

    private function getUncompressedSize(): int
    {
        $zip = new ZipArchive();

        if ($zip->open($this->file->path()) !== true) return PHP_INT_MAX;

        $size = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            if ($size > self::UNCOMPRESSED_SIZE_LIMIT) continue;
            $size += $zip->statIndex($i)['size'] ?? 0;
        }

        $zip->close();

        return $size;
    }

    protected function validateExcelRow(array $record, array $rules, array $errorMessages, int $row): bool
    {
        $validator = Validator::make(
            data: $record,
            rules: $rules,
            messages: $errorMessages,
        );

        $errors = $validator->errors()->messages();

        if (!$errors) return true;

        foreach ($errors as $errorKey => $error) {
            $errorKey = strstr($errorKey, '.', true) ?: $errorKey;

            $column = $this->excelColumnIndexes[$errorKey] + 1;
            $column = Coordinate::stringFromColumnIndex($column);
            $cell = $column . $row;

            $errorMessage = implode(' ', Arr::dot($error));

            $this->validator->errors()->add('file', __('Ошибка в ячейке ":cell"', [
                'cell' => $cell . " ($errorMessage)",
            ]));
        }

        return false;
    }

    protected function passedValidation(): void
    {
        parent::passedValidation();

        if (!$this->fileContent) {
            $this->validator->errors()->add('file', __('Файл не содержит записей'));
            $this->failedValidation($this->validator);
        }

        user()->updateQuietly(['is_excel_import_running' => true]);
    }
}
