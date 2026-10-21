<?php

namespace Http\Common\Record\Controllers;

use App\Controllers\Controller;
use App\Helpers\StringHelper;
use App\Requests\FormRequest;
use App\Rules\ExistsWithOldRule;
use App\Rules\UniqueRule;
use Http\Common\Record\DTOs\RecordCreateParamsDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Modules\Product\Models\Brand;
use Modules\Product\Models\Option;
use Modules\Product\Models\Specification;
use Modules\User\Enums\UserTypeEnum;

class CreateController extends Controller
{
    public function __invoke(string $type): JsonResponse
    {
        $params = $this->params($type);
        $model = $params->model;

        $formRequest = new class($params) extends FormRequest
        {
            public function __construct(private RecordCreateParamsDTO $params)
            {
                parent::__construct();
            }

            protected function nonLocalizedRules(): array
            {
                return $this->params->nonLocalizedRules;
            }

            protected function localizedRules(): array
            {
                return $this->params->localizedRules;
            }
        };

        request()->merge(StringHelper::escapeTags(request()->all()));

        $rules = $formRequest->rules();
        $validatedData = request()->validate($rules);

        $model->safelySave($validatedData);
        $model->unsetRelations();

        $data = $model->resourceClass::make($model);
        return response()->json($data);
    }

    private function params(string $type): RecordCreateParamsDTO
    {
        $langCodes = Arr::pluck(singletons()->system->languages->all, 'code');
        $langCodes = array_combine($langCodes, $langCodes);

        $params = match ($type) {
            'product_brand' => new RecordCreateParamsDTO(
                model: new Brand(),
                nonLocalizedRules: [
                    'name' => [
                        'required',
                        'string',
                        'max:255',
                        new UniqueRule(
                            model: new Brand(),
                            isFieldLocalized: false,
                        ),
                    ],
                ],
                isAccessible: UserTypeEnum::isCompanyType(user()->type),
            ),
            'product_option' => new RecordCreateParamsDTO(
                model: new Option(),
                nonLocalizedRules: [
                    'specification_id' => [
                        'required',
                        'integer',
                        new ExistsWithOldRule(
                            model: new Option(),
                            query: Specification::query(),
                        ),
                    ],
                ],
                localizedRules: [
                    'name' => [
                        'required',
                        'string',
                        'max:255',
                        new UniqueRule(
                            model: new Option(),
                            isFieldLocalized: true,
                            additionalQuery: function ($query) {
                                $query->where('specification_id', request()->post('specification_id'));
                            },
                        ),
                    ],
                ],
                isAccessible: UserTypeEnum::isCompanyType(user()->type),
            ),
            'product_specification' => new RecordCreateParamsDTO(
                model: new Specification([
                    'description' => array_map(fn($value) => null, $langCodes),
                ]),
                localizedRules: [
                    'name' => [
                        'required',
                        'string',
                        'max:255',
                        new UniqueRule(
                            model: new Specification(),
                            isFieldLocalized: true,
                        ),
                    ],
                ],
                isAccessible: UserTypeEnum::isCompanyType(user()->type),
            ),
            default => abort(404),
        };

        if (!$params->isAccessible) abort(404);

        return $params;
    }
}
