<?php

namespace Http\Admin\System\Controllers;

use App\Controllers\ResourceController;
use Http\Admin\System\Requests\LanguageRequest;
use Modules\System\Models\Language;
use Modules\System\Search\LanguageSearch;

class LanguageController extends ResourceController
{
    public function __construct()
    {
        parent::__construct(
            model: new Language(),
            search: new LanguageSearch(),
            formRequestClass: LanguageRequest::class,
        );
    }
}
