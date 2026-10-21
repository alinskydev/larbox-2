<?php

namespace Http\Company\Product\Controllers;

use App\Controllers\ResourceController;
use App\Excel\Export\ExcelExportHelper;
use App\Helpers\PdfHelper;
use Http\Company\Product\Controllers\Traits\Product\ProductControllerExcelImportTrait;
use Http\Company\Product\Filters\ProductFilter;
use Http\Company\Product\Requests\ProductRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Product\Excel\Export\Jobs\ProductExcelExportJob;
use Modules\Product\Models\Product;
use Modules\Product\Search\ProductSearch;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends ResourceController
{
    use ProductControllerExcelImportTrait;

    public function __construct()
    {
        parent::__construct(
            model: new Product(),
            search: new ProductSearch(),
            filter: new ProductFilter(),
            formRequestClass: ProductRequest::class,
        );
    }

    public function index(): JsonResponse|StreamedResponse
    {
        switch (request()->query('format')) {
            case 'excel':
                return ExcelExportHelper::process(
                    jobClass: ProductExcelExportJob::class,
                    query: $this->search->query,
                );

            default:
                $response = parent::index();
                $data = $response->getData(true);

                $data['summary'] = $this->search->query
                    ->cloneForSummary()
                    ->select([
                        DB::raw(implode(', ', [
                            'count(*) filter (where parent_id is null) as parent_count',
                            'count(*) filter (where parent_id is not null) as children_count',
                        ])),
                    ])
                    ->getQuery()
                    ->first();

                return $response->setData($data);
        }
    }

    public function show(int $id): JsonResponse|StreamedResponse
    {
        return match (request()->query('format')) {
            'pdf' => PdfHelper::download(
                path: 'modules/Product/PDF',
                name: 'product',
                params: [
                    'product' => $this->search->query->findOrFail($id),
                ],
            ),
            default => parent::show($id),
        };
    }
}
