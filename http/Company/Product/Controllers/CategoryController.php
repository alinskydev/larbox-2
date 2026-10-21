<?php

namespace Http\Company\Product\Controllers;

use App\NestedSet\NestedSetController;
use Http\Company\Product\Filters\CategoryFilter;
use Http\Company\Product\Requests\CategoryRequest;
use Modules\Product\Models\Category;
use Modules\Product\Search\CategorySearch;

class CategoryController extends NestedSetController
{
    public function __construct()
    {
        parent::__construct(
            model: new Category(),
            search: new CategorySearch(),
            filter: new CategoryFilter(),
            formRequestClass: CategoryRequest::class,
        );
    }
}
