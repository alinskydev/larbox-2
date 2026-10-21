<?php

namespace Modules\Product\Excel\Export\Writers;

use App\Excel\Export\ExcelExportHelper;
use App\Excel\Export\ExcelExportWriter;

class ProductExcelExportWriter extends ExcelExportWriter
{
    protected int $maxColumn = 7;

    protected function beforeFilling(): void
    {
        $this->sumColumns = [5];

        $this->formatGroups = [
            '### ### ### ##0.00' => [5],
        ];

        $this->sheet->setColWidth(1, 50);
        $this->sheet->setColWidth(2, 35);
        $this->sheet->setColWidth(3, 35);
        $this->sheet->setColWidth(4, 35);
        $this->sheet->setColWidth(5, 25);
        $this->sheet->setColWidth(6, 25);
        $this->sheet->setColWidth(7, 25);
    }

    protected function fillHeaderCells(): void
    {
        $this->sheet->writeTo([1, 1], __('fields.full_text'));
        $this->sheet->writeTo([2, 1], __('fields.company_id'));
        $this->sheet->writeTo([3, 1], __('fields.category_id'));
        $this->sheet->writeTo([4, 1], __('fields.brand_id'));
        $this->sheet->writeTo([5, 1], __('fields.price'));
        $this->sheet->writeTo([6, 1], __('fields.barcode'));
        $this->sheet->writeTo([7, 1], __('fields.created_at'));
    }

    protected function fillBodyCells(): void
    {
        $locale = app()->getLocale();

        foreach ($this->data as $record) {
            $categoryName = json_decode($record->category->name, true);

            $this->sheet->writeRow(ExcelExportHelper::sanitizeRow([
                $record->full_text,
                $record->company->name,
                $categoryName[$locale] ?? '',
                $record->brand->name,
                $record->price?->price,
                $record->barcode,
                date('d.m.Y H:i', strtotime($record->created_at)),
            ]));
        }
    }

    protected function afterFilling(): void
    {
        $this->tryToHideColumns([
            'full_text' => 1,
            'company_id' => 2,
            'category_id' => 3,
            'brand_id' => 4,
            'price' => 5,
            'barcode' => 6,
            'created_at' => 7,
        ]);
    }
}
