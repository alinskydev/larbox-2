<?php

namespace Http\Admin\Company\Controllers;

use App\Controllers\ResourceController;
use Http\Admin\Company\Filters\CompanyFilter;
use Http\Admin\Company\Requests\Company\CompanyUpdateCredentialRequest;
use Http\Admin\Company\Requests\CompanyRequest;
use Illuminate\Http\JsonResponse;
use Modules\Company\Models\Company;
use Modules\Company\Search\CompanySearch;
use Modules\System\Enums\DailyLimitActionTypeEnum;
use Modules\System\Enums\DailyLimitEntityTypeEnum;
use Modules\System\Models\DailyLimit;

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

    public function resetDailyLimit(Company $model): JsonResponse
    {
        DailyLimit::query()
            ->where('entity_type', DailyLimitEntityTypeEnum::COMPANY->value)
            ->where('entity_id', $model->id)
            ->where('action_type', DailyLimitActionTypeEnum::RECORD->value)
            ->delete();

        return $this->successResponse();
    }

    public function updateCredential(CompanyUpdateCredentialRequest $request): JsonResponse
    {
        $request->model->safelySave($request->validatedData);
        $data = $this->model->resourceClass::make($request->model);

        return response()->json($data);
    }
}
