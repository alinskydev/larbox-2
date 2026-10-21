<?php

namespace Http\Company\Trade\Controllers;

use App\Controllers\ResourceController;
use Http\Company\Trade\Filters\SaleProductFilter;
use Modules\Trade\Models\SaleProduct;
use Modules\Trade\Search\SaleProductSearch;

class SaleProductController extends ResourceController
{
    public function __construct()
    {
        parent::__construct(
            model: new SaleProduct(),
            search: new SaleProductSearch(),
            filter: new SaleProductFilter(),
        );
    }
}
