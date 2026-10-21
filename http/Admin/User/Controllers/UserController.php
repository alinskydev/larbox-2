<?php

namespace Http\Admin\User\Controllers;

use App\Controllers\ResourceController;
use Http\Admin\User\Requests\UserRequest;
use Illuminate\Contracts\Validation\ValidatesWhenResolved;
use Illuminate\Http\JsonResponse;
use Modules\Audit\Enums\RecordActionTypeEnum;
use Modules\Audit\Models\RecordAction;
use Modules\User\Enums\UserTypeEnum;
use Modules\User\Exceptions\UserException;
use Modules\User\Models\User;
use Modules\User\Search\UserSearch;

class UserController extends ResourceController
{
    public function __construct()
    {
        parent::__construct(
            model: new User(),
            search: new UserSearch(),
            formRequestClass: UserRequest::class,
        );
    }

    public function update(ValidatesWhenResolved $request): JsonResponse
    {
        parent::update($request);
        return $this->successResponseWithAccessToken($request->model);
    }

    public function authAs(User $model): JsonResponse
    {
        if (!UserTypeEnum::isCompanyType($model->type)) {
            throw new UserException(UserException::AUTH_AS_NOT_COMPANY_USER);
        }

        $model->getService()->createNewAccessToken('auth_as:' . user()->id);

        (new RecordAction())->safelySave([
            'company_id' => $model->company_id,
            'auditable_type' => $model::class,
            'auditable_id' => $model->id,
            'type' => RecordActionTypeEnum::AUTH_AS->value,
            'old_values' => [],
            'new_values' => [],
        ]);

        return response()->json(['token' => $model->newAccessToken]);
    }
}
