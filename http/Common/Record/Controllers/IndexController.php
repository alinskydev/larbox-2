<?php

namespace Http\Common\Record\Controllers;

use App\Controllers\Controller;
use App\Helpers\ClassHelper;
use App\Helpers\RequestHelper;
use Http\Common\Record\DTOs\RecordIndexParamsDTO;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Modules\Company\Models\Company;
use Modules\Product\Models\Brand;
use Modules\Product\Models\Category;
use Modules\Product\Models\Option;
use Modules\Product\Models\Product;
use Modules\Product\Models\Specification;
use Modules\User\Enums\UserTypeEnum;
use Modules\User\Helpers\Role\RolePermissionHelper;
use Modules\User\Models\Role;
use Modules\User\Models\User;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class IndexController extends Controller
{
    private array $filter;
    private array $show;

    public function __invoke(string $type): JsonResponse
    {
        $this->filter = (array)request()->query('filter');
        $this->show = (array)request()->query('show');

        if ($type === 'product_category_tree') return $this->productCategoryTree();

        $params = $this->params($type);
        $model = $params->query->getModel();
        $table = $model->getTable();

        $query = $params->query
            ->select(array_merge(
                [
                    "$table.id",
                    DB::raw("$params->field as value"),
                ],
                $params->additionalFields,
            ))
            ->orderBy("$table.id", 'desc');

        if ($ids = Arr::get($this->filter, 'id')) {
            $query->whereIn("$table.id", (array)$ids);
        }

        if ($value = Arr::get($this->filter, 'value')) {
            $query->whereIlike(DB::raw($params->field), $value);
        }

        if (ClassHelper::uses($model, SoftDeletes::class)) {
            $query->addSelect("$table.deleted_at");

            if (in_array('with_deleted', $this->show)) {
                $query->withTrashed();
            }

            if (in_array('only_deleted', $this->show)) {
                $query->onlyTrashed();
            }
        }

        $result = $query->paginate(RequestHelper::pageSize(), page: RequestHelper::page());
        $result = $model->resourceClass::collection($result);

        return $result->response();
    }

    private function params(string $type): RecordIndexParamsDTO
    {
        $locale = app()->getLocale();

        $params = match ($type) {
            'company' => new RecordIndexParamsDTO(
                query: Company::query()
                    ->filterByNestedCompanies('id')
                    ->filterByAdministeredCompanies('id')
                    ->when(isset($this->filter['is_root']), function ($query) {
                        if ((bool)$this->filter['is_root']) {
                            $query->whereNull('parent_id');
                        } else {
                            $query->whereNotNull('parent_id');
                        }
                    }),
                field: 'name',
            ),
            'product' => new RecordIndexParamsDTO(
                query: Product::query()
                    ->with([
                        'parent',
                        'category.ancestors',
                        'brand',
                    ])
                    ->when(isset($this->filter['full_text_or_barcode']), function ($query) {
                        $query->where(function ($q) {
                            $q
                                ->whereIlike('full_text', $this->filter['full_text_or_barcode'])
                                ->orWhere('barcode', $this->filter['full_text_or_barcode']);
                        });
                    })
                    ->when(isset($this->filter['parent_id']), function ($query) {
                        $query->when(
                            $this->filter['parent_id'] === 'none',
                            fn($q) => $q->whereNull('parent_id'),
                            fn($q) => $q->where('parent_id', $this->filter['parent_id']),
                        );
                    })
                    ->when(isset($this->filter['category_id']), function ($query) {
                        $query->whereHas('category', function ($q) {
                            $q
                                ->where('id', $this->filter['category_id'])
                                ->orWhereHas('ancestors', function ($q2) {
                                    $q2->where('id', $this->filter['category_id']);
                                });
                        });
                    }),
                field: 'full_text',
                additionalFields: [
                    'company_id',
                    'parent_id',
                    'category_id',
                    'brand_id',
                    'barcode',
                    'image_list',
                    'image_list_thumbs',
                ],
                isAccessible: UserTypeEnum::isCompanyType(user()->type),
            ),
            'product_brand' => new RecordIndexParamsDTO(
                query: Brand::query()
                    ->filterByOwnRecords(RolePermissionHelper::ROUTE_COMPANY_PRODUCT_BRAND),
                field: 'name',
                isAccessible: UserTypeEnum::isCompanyType(user()->type),
            ),
            'product_category_list' => new RecordIndexParamsDTO(
                query: Category::query()
                    ->with(['ancestors'])
                    ->whereNotNull('parent_id')
                    ->orderBy('lft'),
                field: "name->>'$locale'",
                additionalFields: [
                    'name',
                    'lft',
                    'rgt',
                ],
                isAccessible: UserTypeEnum::isCompanyType(user()->type),
            ),
            'product_option' => new RecordIndexParamsDTO(
                query: Option::query()
                    ->when(isset($this->filter['company_id']), function ($query) {
                        $query->whereIn('company_id', (array)$this->filter['company_id']);
                    })
                    ->when(isset($this->filter['specification_id']), function ($query) {
                        $query->where('specification_id', $this->filter['specification_id']);
                    }),
                field: "name->>'$locale'",
                isAccessible: UserTypeEnum::isCompanyType(user()->type),
            ),
            'product_specification' => new RecordIndexParamsDTO(
                query: Specification::query(),
                field: "name->>'$locale'",
                isAccessible: UserTypeEnum::isCompanyType(user()->type),
            ),
            'user' => new RecordIndexParamsDTO(
                query: User::query()
                    ->filterByNestedCompanies('user.company_id')
                    ->filterByAdministeredCompanies('user.company_id')
                    ->leftJoin('user_profile', 'user_profile.user_id', '=', 'user.id')
                    ->when(user()->type === UserTypeEnum::COMPANY_EMPLOYEE->value, function ($query) {
                        $query->filterByUser('user.id');
                    })
                    ->when(isset($this->filter['company_id']), function ($query) {
                        $query->where('user.company_id', $this->filter['company_id']);
                    })
                    ->when(isset($this->filter['type']), function ($query) {
                        $query->where('user.type', $this->filter['type']);
                    }),
                field: "concat(user_profile.last_name, ' ', user_profile.first_name)",
                additionalFields: [
                    'user.type',
                    'user.username',
                    'user_profile.position',
                ],
            ),
            'user_role' => new RecordIndexParamsDTO(
                query: Role::query()
                    ->filterByCurrentCompany()
                    ->where(function ($query) {
                        $query
                            ->filterByAdministeredCompanies()
                            ->orWhereNull('company_id');
                    })
                    ->when(isset($this->filter['type']), function ($query) {
                        $query->where('type', $this->filter['type']);
                    }),
                field: 'name',
            ),
            default => throw new NotFoundHttpException(),
        };

        if (!$params->isAccessible) {
            throw new NotFoundHttpException();
        }

        return $params;
    }

    private function productCategoryTree(): JsonResponse
    {
        if (!UserTypeEnum::isCompanyType(user()->type)) {
            throw new NotFoundHttpException();
        }

        $parentId = Category::query()->whereNull('parent_id')->firstOrFail()->id;

        $response = Category::query()
            ->withTrashed()
            ->get()
            ->toTree($parentId)
            ->toArray();

        return response()->json($response);
    }
}
