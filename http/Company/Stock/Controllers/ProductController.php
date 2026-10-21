<?php

namespace Http\Company\Stock\Controllers;

use App\Controllers\ResourceController;
use Http\Company\Stock\Filters\ProductFilter;
use Modules\Stock\Models\Product;
use Modules\Stock\Search\ProductSearch;

class ProductController extends ResourceController
{
    public function __construct()
    {
        parent::__construct(
            model: new Product(),
            search: new ProductSearch(),
            filter: new ProductFilter(),
        );
    }
}
