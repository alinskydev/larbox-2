<?php

namespace Http\Company\Product\Controllers;

use App\Controllers\ResourceController;
use Http\Company\Product\Filters\SpecificationFilter;
use Http\Company\Product\Requests\SpecificationRequest;
use Modules\Product\Models\Specification;
use Modules\Product\Search\SpecificationSearch;

class SpecificationController extends ResourceController
{
    public function __construct()
    {
        parent::__construct(
            model: new Specification(),
            search: new SpecificationSearch(),
            filter: new SpecificationFilter(),
            formRequestClass: SpecificationRequest::class,
        );
    }
}
