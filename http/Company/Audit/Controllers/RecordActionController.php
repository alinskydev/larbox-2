<?php

namespace Http\Company\Audit\Controllers;

use App\Controllers\ResourceController;
use Http\Company\Audit\Filters\RecordActionFilter;
use Modules\Audit\Models\RecordAction;
use Modules\Audit\Search\RecordActionSearch;

class RecordActionController extends ResourceController
{
    public function __construct()
    {
        parent::__construct(
            model: new RecordAction(),
            search: new RecordActionSearch(),
            filter: new RecordActionFilter(),
        );
    }
}
