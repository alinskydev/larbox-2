<?php

namespace Http\Common\User\Controllers;

use App\Controllers\ResourceController;
use App\Helpers\Controller\ControllerStreamHelper;
use Http\Common\User\Filters\NotificationFilter;
use Illuminate\Http\JsonResponse;
use Modules\User\Enums\NotificationTypeEnum;
use Modules\User\Exceptions\NotificationException;
use Modules\User\Models\Notification;
use Modules\User\Resources\NotificationResource;
use Modules\User\Search\NotificationSearch;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NotificationController extends ResourceController
{
    public function __construct()
    {
        parent::__construct(
            model: new Notification(),
            search: new NotificationSearch(),
            filter: new NotificationFilter(),
            resourceClass: NotificationResource::class,
        );
    }

    public function show(int $id): JsonResponse
    {
        $this->search->query
            ->clone()
            ->whereKey($id)
            ->where('is_seen', 0)
            ->getQuery()
            ->update(['is_seen' => 1]);

        return parent::show($id);
    }

    public function download(Notification $model): StreamedResponse
    {
        if (!in_array($model->type, [
            NotificationTypeEnum::EXCEL_EXPORT_COMPLETED->value,
        ])) abort(404);

        $name = $model->params['file'] ?? '';
        $file = storage_path("larbox/_tmp/$name");

        if (!is_file($file)) {
            throw new NotificationException(NotificationException::FILE_DELETED);
        }

        return ControllerStreamHelper::download(
            name: $name,
            path: $file,
            shouldBeDeleted: true,
        );
    }

    public function markAllSeen(): JsonResponse
    {
        $this->search->query->where('is_seen', 0)->getQuery()->update(['is_seen' => 1]);
        return $this->successResponse();
    }
}
