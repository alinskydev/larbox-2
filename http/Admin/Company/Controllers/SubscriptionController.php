<?php

namespace Http\Admin\Company\Controllers;

use App\Controllers\ResourceController;
use Http\Admin\Company\Requests\SubscriptionRequest;
use Modules\Company\Models\Subscription;
use Modules\Company\Search\SubscriptionSearch;

class SubscriptionController extends ResourceController
{
    public function __construct()
    {
        parent::__construct(
            model: new Subscription(),
            search: new SubscriptionSearch(),
            formRequestClass: SubscriptionRequest::class,
        );
    }
}
