<?php

namespace Http\Company\Product\Controllers;

use App\Controllers\ResourceController;
use App\Helpers\Controller\ControllerModelHelper;
use Http\Company\Product\Filters\BrandFilter;
use Http\Company\Product\Requests\BrandRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Modules\Product\Models\Brand;
use Modules\Product\Search\BrandSearch;

class BrandController extends ResourceController
{
    public function __construct()
    {
        parent::__construct(
            model: new Brand(),
            search: new BrandSearch(),
            filter: new BrandFilter(),
            formRequestClass: BrandRequest::class,
        );
    }

    public function setName(Brand $model, string $value): JsonResponse
    {
        ControllerModelHelper::setValues(
            model: $model,
            values: ['name' => $value],
            rules: ['name' => 'required|string|max:255'],
            condition: ['name' => Rule::notIn(['Brand 2'])],
            conditionErrorMessage: __('Нельзя изменять название для ":value"', [
                'value' => 'Brand 2',
            ]),
        );

        return $this->successResponse();
    }
}
