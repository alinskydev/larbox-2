<?php

namespace Http\Company\Trade\Controllers;

use App\Controllers\ResourceController;
use Http\Company\Trade\Filters\RecalculationProductFilter;
use Http\Company\Trade\Requests\RecalculationProductRequest;
use Modules\Trade\Models\RecalculationProduct;
use Modules\Trade\Search\RecalculationProductSearch;

class RecalculationProductController extends ResourceController
{
    public function __construct()
    {
        parent::__construct(
            model: new RecalculationProduct(),
            search: new RecalculationProductSearch(),
            filter: new RecalculationProductFilter(),
            formRequestClass: RecalculationProductRequest::class,
        );
    }
}
