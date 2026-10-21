<?php

namespace Http\Company\Product\Requests\Product;

use App\Excel\Import\ExcelImportFormRequest;
use App\Helpers\StringHelper;
use App\Helpers\Validation\ValidationFileRulesHelper;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\Company\Observers\Subscription\SubscriptionCheckLimitObserver;
use Modules\Product\Models\Brand;
use Modules\Product\Models\Category;
use Modules\Product\Models\Product;
use Modules\Product\Models\Product\ProductImport;
use Modules\System\Observers\DailyLimit\DailyLimitCreateObserver;
use Modules\User\Helpers\Role\RolePermissionHelper;

class ProductExcelImportProcessRequest extends ExcelImportFormRequest
{
    protected array $excelColumns = [
        'name',
        'category_id',
        'brand_id',
        'barcode',
        'description',
    ];

    public function nonLocalizedRules(): array
    {
        return [
            'file' => [
                'required',
                ...ValidationFileRulesHelper::excel(),
            ],
        ];
    }

    protected function additionalValidation(): void
    {
        $isCorrectExcel = $this->checkAndPrepareExcel(
            lastColumn: 'E',
            rowLimit: 10000,
        );

        if (!$isCorrectExcel) return;

        $locale = app()->getLocale();

        $names = array_map(fn($value) => mb_strtolower($value[0]), $this->fileContent);
        $duplicateNames = array_diff_assoc($names, array_unique($names));

        if ($duplicateNames) {
            $this->validator->errors()->add('file', __('Продукты ":products" повторяются', [
                'products' => implode(', ', $duplicateNames),
            ]));

            return;
        }

        $barcodes = Arr::pluck($this->fileContent, 3);
        $duplicateBarcodes = array_diff_assoc($barcodes, array_unique($barcodes));

        if ($duplicateBarcodes) {
            $this->validator->errors()->add('file', __('Штрихкоды ":barcodes" повторяются', [
                'barcodes' => implode(', ', $duplicateBarcodes),
            ]));

            return;
        }

        $products = ProductImport::query()
            ->select([
                'id',
                DB::raw('LOWER(name) as name'),
            ])
            ->filterByNestedCompanies()
            ->whereNull('parent_id')
            ->whereIn(DB::raw('LOWER(name)'), $names)
            ->getQuery()
            ->get()
            ->keyBy('name')
            ->toArray();

        $productBarcodes = ProductImport::query()
            ->select([
                'id',
                'barcode',
            ])
            ->whereIn('barcode', $barcodes)
            ->getQuery()
            ->get()
            ->pluck('id', 'barcode')
            ->toArray();

        $categories = Category::query()
            ->select([
                'id',
                DB::raw("LOWER(name->>'$locale') as name"),
            ])
            ->whereNotNull('parent_id')
            ->whereIn(DB::raw("LOWER(name->>'$locale')"), array_map(fn($value) => mb_strtolower($value[1]), $this->fileContent))
            ->getQuery()
            ->get()
            ->pluck('id', 'name')
            ->toArray();

        $brands = Brand::query()
            ->select([
                'id',
                DB::raw('LOWER(name) as name'),
            ])
            ->filterByOwnRecords(RolePermissionHelper::ROUTE_COMPANY_PRODUCT_BRAND)
            ->whereIn(DB::raw('LOWER(name)'), array_map(fn($value) => mb_strtolower($value[2]), $this->fileContent))
            ->getQuery()
            ->get()
            ->pluck('id', 'name')
            ->toArray();

        (new SubscriptionCheckLimitObserver())->creating(
            model: new Product(['company_id' => user()->company_id]),
            additionalCount: $this->excelSheetInfo['totalRows'] - count($products) - 1,
        );

        foreach ($this->fileContent as $key => &$record) {
            $row = $key + 1;

            $record = array_map(fn($index) => StringHelper::normalizeSpaces($record[$index]), $this->excelColumnIndexes);

            if ($record['name'] === null) {
                unset($this->fileContent[$key]);
                continue;
            }

            $model = $products[mb_strtolower($record['name'])] ?? new ProductImport();
            $record['id'] = $model->id;

            $categoryName = mb_strtolower($record['category_id']);
            $brandName = mb_strtolower($record['brand_id']);
            $barcodeProductId = $productBarcodes[$record['barcode']] ?? null;

            $record['category_id'] = $categories[$categoryName] ?? 0;
            $record['brand_id'] = $brands[$brandName] ?? 0;

            $isCorrectExcelRow = $this->validateExcelRow(
                record: $record,
                rules: [
                    'name' => 'required|string|max:255',
                    'description' => 'nullable|string|max:10000',
                    'category_id' => 'required|integer|min:1',
                    'brand_id' => 'required|integer|min:1',
                    'barcode' => [
                        'required',
                        'digits_between:5,13',
                        $barcodeProductId && $barcodeProductId !== $model->id ? 'max:0' : '',
                    ],
                ],
                errorMessages: [
                    'category_id' => [
                        'min' => [
                            'numeric' => __('Выбранная запись не существует или была удалена'),
                        ],
                    ],
                    'brand_id' => [
                        'min' => [
                            'numeric' => __('Выбранная запись не существует или была удалена'),
                        ],
                    ],
                    'barcode' => [
                        'max' => [
                            'string' => __('Данное значение поля уже используется в записи ":pk"', [
                                'pk' => $barcodeProductId,
                            ]),
                        ],
                    ],
                ],
                row: $row,
            );

            if (!$isCorrectExcelRow) return;

            if (!$record['description']) Arr::forget($record, 'description');
        }

        $newRecordCount = count(array_filter($this->fileContent, fn($value) => !$value['id']));

        if ($newRecordCount) {
            (new DailyLimitCreateObserver())->creating(
                model: new Product(['company_id' => user()->company_id]),
                decreaseValue: $newRecordCount,
            );
        }
    }
}
