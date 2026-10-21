<?php

namespace Http\Company\Trade\Controllers;

use App\Controllers\ResourceController;
use Http\Company\Trade\Filters\PurchaseFilter;
use Http\Company\Trade\Requests\Purchase\PurchaseAddProductsRequest;
use Http\Company\Trade\Requests\Purchase\PurchaseRemoveProductsRequest;
use Http\Company\Trade\Requests\PurchaseRequest;
use Illuminate\Http\JsonResponse;
use Modules\Trade\Enums\PurchaseStatusEnum;
use Modules\Trade\Models\Purchase;
use Modules\Trade\Search\PurchaseSearch;

class PurchaseController extends ResourceController
{
    public function __construct()
    {
        parent::__construct(
            model: new Purchase(),
            search: new PurchaseSearch(),
            filter: new PurchaseFilter(),
            formRequestClass: PurchaseRequest::class,
        );
    }

    public function addProducts(PurchaseAddProductsRequest $request): JsonResponse
    {
        $products = $request->validatedData['products'];
        $request->model->getService()->addProducts($products);

        return $this->successResponse();
    }

    public function removeProducts(PurchaseRemoveProductsRequest $request): JsonResponse
    {
        $productIds = $request->validatedData['product_ids'];
        $request->model->getService()->removeProducts($productIds);

        return $this->successResponse();
    }

    public function complete(Purchase $model): JsonResponse
    {
        $model->safelySave(['status' => PurchaseStatusEnum::COMPLETED->value]);
        return $this->successResponse();
    }

    public function cancel(Purchase $model): JsonResponse
    {
        $model->safelySave(['status' => PurchaseStatusEnum::CANCELLED->value]);
        return $this->successResponse();
    }
}
