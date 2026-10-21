<?php

namespace App\Excel\Import;

use App\Helpers\StringHelper;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelImportHelper
{
    public static function downloadExample(string $path): StreamedResponse
    {
        $file = base_path($path);
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray();

        foreach ($data[0] as &$field) {
            if ($field === null) continue;

            $fieldParts = explode(' ', $field);
            $fieldParts[0] = $fieldParts[0] ? __("fields.{$fieldParts[0]}") : '';
            $field = implode(' ', $fieldParts);
        }

        $sheet->fromArray($data);
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(
            callback: function () use ($writer) {
                $writer->save('php://output');
            },
            name: StringHelper::uuid() . '.xlsx',
        );
    }
}
