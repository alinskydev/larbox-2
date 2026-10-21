<?php

namespace Http\Admin\User\Controllers;

use App\Controllers\ResourceController;
use Http\Admin\User\Requests\NotificationRequest;
use Illuminate\Contracts\Validation\ValidatesWhenResolved;
use Illuminate\Http\JsonResponse;
use Modules\User\Jobs\PrepareAndCreateNotificationsJob;
use Modules\User\Models\Notification;

class NotificationController extends ResourceController
{
    public function __construct()
    {
        parent::__construct(
            model: new Notification(),
            formRequestClass: NotificationRequest::class,
        );
    }

    public function create(ValidatesWhenResolved $request): JsonResponse
    {
        dispatch(new PrepareAndCreateNotificationsJob($request->validatedData));
        return $this->successResponse();
    }
}
