<?php

namespace Http\Company\Product\Controllers;

use App\Controllers\ResourceController;
use Http\Company\Product\Filters\OptionFilter;
use Http\Company\Product\Requests\OptionRequest;
use Modules\Product\Models\Option;
use Modules\Product\Search\OptionSearch;

class OptionController extends ResourceController
{
    public function __construct()
    {
        parent::__construct(
            model: new Option(),
            search: new OptionSearch(),
            filter: new OptionFilter(),
            formRequestClass: OptionRequest::class,
        );
    }
}
