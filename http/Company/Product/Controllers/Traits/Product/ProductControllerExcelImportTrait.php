<?php

namespace Http\Company\Product\Controllers\Traits\Product;

use App\Controllers\Controller;
use App\Excel\Import\ExcelImportHelper;
use App\Excel\Import\ExcelImportJob;
use Http\Company\Product\Requests\Product\ProductExcelImportProcessRequest;
use Illuminate\Http\JsonResponse;
use Modules\Product\Models\Product\ProductImport;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @mixin Controller
 */
trait ProductControllerExcelImportTrait
{
    public function excelImportExample(): StreamedResponse
    {
        return ExcelImportHelper::downloadExample('modules/Product/Excel/Import/Examples/products.xlsx');
    }

    public function excelImportProcess(ProductExcelImportProcessRequest $request): JsonResponse
    {
        $recordGroups = array_chunk($request->fileContent, 1000);

        foreach ($recordGroups as $key => $records) {
            dispatch(new ExcelImportJob(
                modelClass: ProductImport::class,
                records: $records,
                recordLabel: 'name',
                isLast: array_key_last($recordGroups) === $key,
            ));
        }

        return $this->successResponse();
    }
}
