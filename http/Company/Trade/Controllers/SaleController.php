<?php

namespace Http\Company\Trade\Controllers;

use App\Controllers\ResourceController;
use Http\Company\Trade\Filters\SaleFilter;
use Http\Company\Trade\Requests\Sale\SaleReturnRequest;
use Http\Company\Trade\Requests\SaleRequest;
use Illuminate\Http\JsonResponse;
use Modules\Trade\Models\Sale;
use Modules\Trade\Search\SaleSearch;

class SaleController extends ResourceController
{
    public function __construct()
    {
        parent::__construct(
            model: new Sale(),
            search: new SaleSearch(),
            filter: new SaleFilter(),
            formRequestClass: SaleRequest::class,
        );
    }

    public function return(SaleReturnRequest $request): JsonResponse
    {
        $products = $request->validatedData['products'];
        $transactions = $request->validatedData['transactions'];
        $request->model->getService()->return($products, $transactions);

        return $this->successResponse();
    }
}
