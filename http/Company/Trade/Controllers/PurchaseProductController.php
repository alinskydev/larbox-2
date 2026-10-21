<?php

namespace Http\Company\Trade\Controllers;

use App\Controllers\ResourceController;
use Http\Company\Trade\Filters\PurchaseProductFilter;
use Modules\Trade\Models\PurchaseProduct;
use Modules\Trade\Search\PurchaseProductSearch;

class PurchaseProductController extends ResourceController
{
    public function __construct()
    {
        parent::__construct(
            model: new PurchaseProduct(),
            search: new PurchaseProductSearch(),
            filter: new PurchaseProductFilter(),
        );
    }
}
