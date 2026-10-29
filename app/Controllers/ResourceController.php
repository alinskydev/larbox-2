<?php

namespace App\Controllers;

use App\Base\Filter;
use App\Base\Model;
use App\Base\Resource;
use App\Helpers\ClassHelper;
use App\Helpers\RequestHelper;
use App\Search\Search;
use Illuminate\Contracts\Validation\ValidatesWhenResolved;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Modules\Seo\Traits\SeoMetaModelTrait;

class ResourceController extends Controller
{
    /**
     * @param class-string<Resource> $resourceClass
     */
    public function __construct(
        public Model $model,
        protected Search $search = new Search(),
        protected ?Filter $filter = null,
        protected ?string $resourceClass = null,
        protected ?string $formRequestClass = null,
    ) {
        $this->resourceClass ??= $this->model->resourceClass;

        $this->middleware(function ($request, $next) {
            if ($this->search !== null) {
                $this->search->setQuery($this->model->query())
                    ->with((array)$request->query('with'))
                    ->filter((array)$request->query('filter'))
                    ->sort((array)$request->query('sort'))
                    ->show((array)$request->query('show'))
                    ->setPageSize((int)$request->query('page_size'));

                if ($this->filter) {
                    $this->filter->process($this->search->query);
                }
            }

            if ($this->formRequestClass !== null) {
                app()->bind(ValidatesWhenResolved::class, $this->formRequestClass);
            }

            return $next($request);
        });
    }

    public function resolveRouteModel(string $value): Model
    {
        $query = $this->filter ? $this->filter->process($this->model->query()) : $this->model->query();
        return $query->where($this->model->getKeyName(), $value)->firstOrFail();
    }

    public function index()
    {
        $paginator = $this->search->query->paginate(
            perPage: $this->search->pageSize,
            page: RequestHelper::page(),
        );

        return $this->resourceClass::collection($paginator)->response();
    }

    public function show(int $id)
    {
        $model = $this->search->query->findOrFail($id);

        if (ClassHelper::uses($model, SeoMetaModelTrait::class)) {
            $model->append(['seo_meta', 'seo_meta_as_array']);
        }

        $response = $this->resourceClass::make($model);
        return response()->json($response);
    }

    public function create(ValidatesWhenResolved $request)
    {
        $request->model->safelySave($request->validatedData);
        $request->model->unsetRelations();

        $response = $this->resourceClass::make($request->model);
        return response()->json($response);
    }

    public function update(ValidatesWhenResolved $request)
    {
        $request->model->safelySave($request->validatedData);
        $request->model->unsetRelations();

        $response = $this->resourceClass::make($request->model);
        return response()->json($response);
    }

    public function delete(Model $model)
    {
        $model->safelyDelete();
        return $this->successResponse();
    }

    public function restore(int $id)
    {
        if (!ClassHelper::uses($this->model, SoftDeletes::class)) {
            throw new \Exception('Model doesn\'t use soft delete trait');
        }

        $this->search->query->onlyTrashed()->findOrFail($id)->safelyRestore();
        return $this->successResponse();
    }

    public function deleteMultiple()
    {
        $selection = Arr::flatten((array)request()->query('selection', []));

        $models = $this->search->query
            ->whereIn($this->model->getRouteKeyName(), $selection)
            ->limit($this->search->pageSize)
            ->get();

        $this->model->safelyDBProcess(function () use ($models) {
            $models->each(function ($model, $key) {
                $model->safelyDelete();
            });
        });

        return $this->successResponse();
    }

    public function restoreMultiple()
    {
        if (!ClassHelper::uses($this->model, SoftDeletes::class)) {
            throw new \Exception('Model doesn\'t use soft delete trait');
        }

        $selection = Arr::flatten((array)request()->query('selection', []));

        $models = $this->search->query
            ->whereIn($this->model->getRouteKeyName(), $selection)
            ->limit($this->search->pageSize)
            ->onlyTrashed()
            ->get();

        $this->model->safelyDBProcess(function () use ($models) {
            $models->each(function ($model, $key) {
                $model->safelyRestore();
            });
        });

        return $this->successResponse();
    }
}
