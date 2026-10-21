<?php

namespace Http\Admin\History\Controllers;

use App\Controllers\ResourceController;
use Modules\History\Models\StoreHttpLog;
use Modules\History\Search\StoreHttpLogSearch;

class StoreHttpLogController extends ResourceController
{
    public function __construct()
    {
        parent::__construct(
            model: new StoreHttpLog(),
            search: new StoreHttpLogSearch(),
        );
    }
}
