<?php

namespace App\Excel\Export;

use App\Helpers\StringHelper;
use avadim\FastExcelWriter\Excel;
use avadim\FastExcelWriter\Sheet;
use Illuminate\Support\LazyCollection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Symfony\Component\HttpFoundation\StreamedResponse;

abstract class ExcelExportWriter
{
    public Excel $spreadsheet;
    protected Sheet $sheet;

    protected LazyCollection $data;
    protected array $sumColumns = [];
    protected array $formatGroups = [];

    protected int $headerMaxRow = 1;
    protected int $dataMinRow;

    protected int $maxColumn;
    protected int $maxRow;

    protected array $excelColumns;

    abstract protected function fillHeaderCells(): void;
    abstract protected function fillBodyCells(): void;

    public function __construct(iterable $data)
    {
        $this->spreadsheet = Excel::create();
        $this->sheet = $this->spreadsheet->sheet();

        $this->dataMinRow = $this->headerMaxRow + 1;
        $this->maxRow = $this->dataMinRow;

        $this->data = LazyCollection::make($data)->tapEach(fn() => $this->maxRow++);
        $this->excelColumns = (array)request()->query('excel_columns');
    }

    public function process(): void
    {
        $this->beforeFilling();
        $this->setStyles();
        $this->fillHeaderCells();
        $this->fillBodyCells();
        $this->fillFooterCells();
        $this->afterFilling();
    }

    public function processAsStream(): StreamedResponse
    {
        $this->process();

        return response()->streamDownload(
            callback: function () {
                $this->spreadsheet->download();
            },
            name: StringHelper::uuid() . '.xlsx',
        );
    }

    protected function fillFooterCells(): void
    {
        $dataMinRow = $this->dataMinRow;
        $dataMaxRow = $this->maxRow - 1;

        $this->sheet->writeTo([1, $this->maxRow], __('Итого'));

        foreach ($this->sumColumns as $columnIndex) {
            $sumCells = $this->rangeFromIndexes($columnIndex, $dataMinRow, $columnIndex, $dataMaxRow);
            $this->sheet->writeTo([$columnIndex, $this->maxRow], "=SUM($sumCells)");
        }

        $this->sheet->setRowStyle(
            rowRange: $this->maxRow,
            style: Excel::newStyle()
                ->setFontStyleBold()
                ->setTextAlign('center', 'center')
                ->setFillColor('#d0cecf')
                ->toArray(),
        );
    }

    protected function setStyles(): void
    {
        // Formatters

        foreach ($this->formatGroups as $formatter => $columns) {
            foreach ($columns as $column) {
                $this->sheet->setColFormat($column, $formatter);
            }
        }

        // Styles

        $this->sheet->setColStyle(
            colRange: "1:$this->maxColumn",
            style: Excel::newStyle()
                ->setTextAlign('left', 'center')
                ->setIndentLeft(1)
                ->setTextWrap(true)
                ->toArray(),
        );

        $this->sheet->setRowStyle(
            rowRange: "1:$this->headerMaxRow",
            style: Excel::newStyle()
                ->setFontStyleBold()
                ->setTextAlign('center', 'center')
                ->setFillColor('#d0cecf')
                ->toArray(),
        );

        // Finalize

        $this->sheet->setFreezeRows($this->headerMaxRow);
        $this->sheet->setActiveCell('A1');
    }

    protected function beforeFilling(): void {}
    protected function afterFilling(): void {}

    public function rangeFromIndexes(
        int $colFrom,
        int $rowFrom,
        int $colTo,
        int $rowTo,
    ): string {
        $colFrom = Coordinate::stringFromColumnIndex($colFrom);
        $colTo = Coordinate::stringFromColumnIndex($colTo);

        return "{$colFrom}{$rowFrom}:{$colTo}{$rowTo}";
    }

    protected function tryToHideColumns(array $ranges): void
    {
        if ($this->excelColumns) {
            foreach ($ranges as $key => $range) {
                if (!in_array($key, $this->excelColumns)) {
                    $range = is_array($range) ? $range : [$range => $range];

                    for ($i = array_key_first($range); $i <= end($range); $i++) {
                        $this->sheet->setColHidden($i);
                    }
                }
            }
        }
    }
}
