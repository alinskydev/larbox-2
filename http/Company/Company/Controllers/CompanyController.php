<?php

namespace Http\Company\Company\Controllers;

use App\Controllers\ResourceController;
use Http\Company\Company\Filters\CompanyFilter;
use Http\Company\Company\Requests\CompanyRequest;
use Modules\Company\Models\Company;
use Modules\Company\Search\CompanySearch;

class CompanyController extends ResourceController
{
    public function __construct()
    {
        parent::__construct(
            model: new Company(),
            search: new CompanySearch(),
            filter: new CompanyFilter(),
            formRequestClass: CompanyRequest::class,
        );
    }
}
