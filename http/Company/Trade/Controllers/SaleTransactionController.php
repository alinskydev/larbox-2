<?php

namespace Http\Company\Trade\Controllers;

use App\Controllers\ResourceController;
use Http\Company\Trade\Filters\SaleTransactionFilter;
use Modules\Trade\Models\SaleTransaction;
use Modules\Trade\Search\SaleTransactionSearch;

class SaleTransactionController extends ResourceController
{
    public function __construct()
    {
        parent::__construct(
            model: new SaleTransaction(),
            search: new SaleTransactionSearch(),
            filter: new SaleTransactionFilter(),
        );
    }
}
