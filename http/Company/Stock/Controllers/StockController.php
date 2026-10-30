<?php

namespace Http\Company\Stock\Controllers;

use App\Controllers\ResourceController;
use Http\Company\Stock\Filters\StockFilter;
use Http\Company\Stock\Requests\StockRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Modules\Stock\Models\Stock;
use Modules\Stock\Search\StockSearch;

class StockController extends ResourceController
{
    public function __construct()
    {
        parent::__construct(
            model: new Stock(),
            search: new StockSearch(),
            filter: new StockFilter(),
            formRequestClass: StockRequest::class,
        );
    }

    public function index(): JsonResponse
    {
        $response = parent::index();
        $data = $response->getData(true);

        $data['summary'] = $this->search->query
            ->cloneForSummary()
            ->leftJoin('stock_product', function ($join) {
                $join
                    ->on('stock_product.stock_id', '=', 'stock.id')
                    ->on('stock_product.company_id', '=', 'stock.company_id');
            })
            ->select(DB::raw('coalesce(sum(stock_product.quantity), 0) as product_quantity'))
            ->getQuery()
            ->first();

        return $response->setData($data);
    }
}
