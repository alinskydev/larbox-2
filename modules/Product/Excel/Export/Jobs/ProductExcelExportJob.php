<?php

namespace Modules\Product\Excel\Export\Jobs;

use App\Excel\Export\ExcelExportJob;
use App\Excel\Export\ExcelExportWriter;
use Modules\Product\Excel\Export\Writers\ProductExcelExportWriter;
use Modules\Product\Models\Product;

class ProductExcelExportJob extends ExcelExportJob
{
    public static int $instantLimit = 50000;
    public static int $totalLimit = 500000;

    public function createWriter(): ExcelExportWriter
    {
        $data = Product::query()
            ->with([
                'company:id,name',
                'category:id,name',
                'brand:id,name',
                'price:id,product_id,price',
            ])
            ->whereRaw("id in ($this->sql)", $this->bindings)
            ->getQueryWithRelations()
            ->lazyByIdDesc(10000);

        return new ProductExcelExportWriter($data);
    }
}
