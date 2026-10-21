<?php

namespace Modules\System\Helpers;

use App\Base\Model;
use App\Controllers\ResourceController;
use App\Helpers\ClassHelper;
use App\Search\Search;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Modules\User\Helpers\RoleHelper;
use Modules\User\Middleware\UserSuperadminMiddleware;
use Modules\User\Models\User;

class UiSchemaHelper
{
    /**
     * @return array<string, array{model: string, soft_deletes: bool, actions: array, columns: array, fields: array, search: array}>
     */
    public static function build(): array
    {
        $result = [];

        foreach (static::routeGroups() as $controllerClass => $routes) {
            $controller = new $controllerClass();
            $model = $controller->model;
            $formRequestClass = UiFieldHelper::property($controller, 'formRequestClass');
            $softDeletes = ClassHelper::uses($model, SoftDeletes::class);
            $key = static::resourceKey(array_keys($routes));

            $result[$key] = [
                'model' => UiFieldHelper::modelName($model),
                'soft_deletes' => $softDeletes,
                'actions' => static::actions($key, $routes),
                'columns' => static::columns($model),
                'fields' => $formRequestClass ? UiFieldHelper::fields(formRequestClass: $formRequestClass, model: $model) : [],
                'search' => static::search(UiFieldHelper::property($controller, 'search'), $softDeletes),
            ];
        }

        ksort($result);

        return $result;
    }

    public static function forUser(User $user, array $schema): array
    {
        $isSuperadmin = $user->getService()->isSuperadmin();

        foreach ($schema as $key => $resource) {
            $actions = array_filter(
                $resource['actions'],
                fn(array $action) => static::canAccessAction($user, $isSuperadmin, $action['name']),
            );

            if (!$actions) {
                unset($schema[$key]);
                continue;
            }

            $schema[$key]['actions'] = $actions;
        }

        return $schema;
    }

    /**
     * @return array<class-string, array<string, \Illuminate\Routing\Route>> routes of every resource controller, keyed by route name
     */
    private static function routeGroups(): array
    {
        $result = [];

        foreach (Route::getRoutes()->getRoutes() as $route) {
            $name = $route->getName();
            $controllerClass = $route->getControllerClass();

            if (!$name || !$controllerClass || !is_subclass_of($controllerClass, ResourceController::class)) continue;

            $result[$controllerClass][$name] = $route;
        }

        return $result;
    }

    /**
     * The shortest route name belongs to a direct action (company.product.brand.index),
     * nested groups (company.product.product.excel-import.example) are always longer.
     *
     * @param string[] $names
     */
    private static function resourceKey(array $names): string
    {
        usort($names, fn(string $a, string $b) => strlen($a) <=> strlen($b));

        return Str::beforeLast($names[0], '.');
    }

    /**
     * @param array<string, \Illuminate\Routing\Route> $routes
     */
    private static function actions(string $key, array $routes): array
    {
        $result = [];

        foreach ($routes as $name => $route) {
            $action = Str::after($name, "$key.");

            $result[$action] = [
                'name' => $name,
                'method' => $route->methods()[0],
                'url' => $route->uri(),
            ];
        }

        return $result;
    }

    private static function columns(Model $model): array
    {
        $columns = $model->getConnection()->getSchemaBuilder()->getColumns($model->getTable());
        $hidden = $model->getHidden();
        $result = [];

        foreach ($columns as $column) {
            $name = $column['name'];

            if (in_array($name, $hidden, true)) continue;

            $result[$name] = [
                'type' => $column['type_name'],
                'label' => UiFieldHelper::label($name),
                'nullable' => $column['nullable'],
            ];
        }

        return $result;
    }

    private static function search(Search $search, bool $softDeletes): array
    {
        $filters = [];

        foreach ($search->filters as $key => $filter) {
            $type = is_array($filter) ? reset($filter['filters']) : $filter;

            $filters[$key] = ['type' => strtolower($type->name)];
        }

        $show = $softDeletes ? ['with_deleted', 'only_deleted'] : [];

        return [
            'filters' => $filters,
            'sorts' => array_keys($search->sorts),
            'default_sort' => $search->defaultSort,
            'relations' => $search->relations,
            'show' => $show,
            'page_size' => $search->pageSize,
        ];
    }

    private static function canAccessAction(User $user, bool $isSuperadmin, string $name): bool
    {
        if (Str::startsWith($name, 'common.')) return true;

        if (!RoleHelper::canAccessRoute(user: $user, route: $name)) return false;

        $middleware = Route::getRoutes()->getByName($name)?->gatherMiddleware() ?? [];

        return $isSuperadmin || !in_array(UserSuperadminMiddleware::class, $middleware, true);
    }
}
