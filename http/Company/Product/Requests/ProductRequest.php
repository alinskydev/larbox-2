<?php

namespace Http\Company\Product\Requests;

use App\Helpers\Validation\ValidationFileRulesHelper;
use App\Requests\ResourceFormRequest;
use App\Rules\ExistsWithOldRule;
use App\Rules\UniqueRule;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Modules\Product\Models\Brand;
use Modules\Product\Models\Category;
use Modules\Product\Models\Option;
use Modules\Product\Models\Product;
use Modules\Seo\Traits\SeoMetaFormRequestTrait;
use Modules\User\Helpers\Role\RolePermissionHelper;

class ProductRequest extends ResourceFormRequest
{
    use SeoMetaFormRequestTrait;

    protected array $imageUploads = [
        'image_list.*' => [
            'source' => 'external',
            'folder' => 'product',
            'sizes' => [1920, 1080],
        ],
    ];

    private ?int $parentId;

    public function __construct()
    {
        parent::__construct();
        $this->parentId = $this->model->exists ? $this->model->parent_id : request()->post('parent_id');
    }

    public function nonLocalizedRules(): array
    {
        return [
            'parent_id' => [
                'nullable',
                'integer',
                new ExistsWithOldRule(
                    model: $this->model,
                    query: Product::query()->whereNull('parent_id'),
                ),
            ],
            'category_id' => [
                Rule::requiredIf(!$this->parentId),
                'integer',
                new ExistsWithOldRule(
                    model: $this->model,
                    query: Category::query()->whereNotNull('parent_id'),
                ),
            ],
            'brand_id' => [
                Rule::requiredIf(!$this->parentId),
                'integer',
                new ExistsWithOldRule(
                    model: $this->model,
                    query: Brand::query()->filterByOwnRecords(RolePermissionHelper::ROUTE_COMPANY_PRODUCT_BRAND),
                ),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                new UniqueRule(
                    model: $this->model,
                    isFieldLocalized: false,
                    additionalQuery: fn($query) => $query->when(
                        $this->parentId,
                        fn($q) => $q->where('parent_id', $this->parentId),
                        fn($q) => $q->whereNull('parent_id'),
                    ),
                ),
            ],
            'description' => 'nullable|string|max:10000',
            'barcode' => [
                'required',
                'digits_between:5,13',
                new UniqueRule(
                    model: $this->model,
                    isFieldLocalized: false,
                ),
            ],
            'image_list' => 'array|max:5',
            ...$this->deletableFileFieldsMultipleValidation(
                field: 'image_list',
                rules: ValidationFileRulesHelper::image(),
            ),

            'options' => [
                'array',
                'max:10',
                new ExistsWithOldRule(
                    model: $this->model,
                    query: Option::query(),
                ),
            ],
            'options.*' => 'required|integer|distinct',
        ];
    }

    protected function passedValidation(): void
    {
        parent::passedValidation();

        $this->validatedData['options'] = array_map(
            fn($value) => [
                'product_company_id' => $this->model->exists ? $this->model->company_id : user()->company_id,
                'option_id' => $value,
            ],
            $this->validatedData['options'] ?? [],
        );

        if ($this->model->exists) {
            Arr::forget($this->validatedData, ['parent_id']);
        }

        if ($this->parentId) {
            Arr::forget($this->validatedData, ['category_id', 'brand_id']);
        }

        $this->model->fillRelations(
            manyToMany: [
                'options' => $this->validatedData['options'] ?? [],
            ],
        );
    }
}
