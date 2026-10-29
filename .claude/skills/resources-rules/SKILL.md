---
name: resources-rules
description: Mandatory standards for API resources in modules/*/Resources (extending App\Base\Resource or App\NestedSet\NestedSetResource) and the model's $resourceClass. Use when creating or editing a resource or deciding how a model is serialized in API responses.
---

# Resources Rules

Location: `modules/<Module>/Resources/<Entity>Resource.php`, namespace `Modules\<Module>\Resources`, base `App\Base\Resource` (nested-set models: `App\NestedSet\NestedSetResource`).
Canonical: `modules/User/Resources/ProfileResource.php`, `modules/User/Resources/RoleResource.php`, `modules/Audit/Resources/RecordActionResource.php`, `modules/Product/Resources/CategoryResource.php`.

## How the base works (`App\Base\Resource`)
- `toArray()` = `$this->resource->attributesToArray()` (visible attributes, `$appends`, `withCount` / `withAggregate` / `addSelect` columns, image `*_thumbs`) merged with every relation in `getRelations()` — i.e. only eager-loaded ones — each wrapped in the related model's own `$resourceClass` (`::collection()` for collections, `::make()` otherwise; null relation → `null`, empty collection → `[]`).
- `Resource::collection()` sets protected `$this->isCollection = true` on every item.
- `App\Base\Model` hides `deleted_at`, `sort_index`, `pivot`, `seo_meta`, appends `is_deleted` on soft-delete models and has default `public string $resourceClass = Resource::class;`. Most models (Product, Brand, Stock, Sale, Purchase, User, Company...) have no own resource.
- `pivot` is a loaded relation, so the base still emits it — hide it in the resource when needed (`RoleResource`).
- `Model::preventLazyLoading()` is on in `local` / `testing` (`AppServiceProvider::boot()`): reading an unloaded relation throws in tests.
- `ResourceController::create()` / `update()` call `$request->model->unsetRelations()` before `make()` — create/update responses carry no relations. `show()` appends `seo_meta`, `seo_meta_as_array` for `SeoMetaModelTrait` models.

## Rules
- Create a resource only to add computed fields, hide/mask values or transform output. Existing (8): `RecordAction`, `Credential`, `StoreHttpLog`, `Category`, `Setting`, `Notification`, `Profile`, `Role`.
- Name `<Entity>Resource`, one per model, in the module owning the model.
- Register on the model: property `public string $resourceClass = <Entity>Resource::class;` (after `$table` / `$casts`) + class PHPDoc line `@property class-string<<Entity>Resource> $resourceClass` in its own block after the relation properties (`modules/User/Models/Role.php`, `modules/Product/Models/Category.php`).
- `ResourceController` uses `$this->model->resourceClass` by default (`$this->resourceClass ??= ...`); pass `resourceClass:` only to really override (`NotificationController` passes the model's own class — redundant, don't copy).
- Outside `ResourceController`, serialize through the model: `$user->resourceClass::make($user)` (`http/Common/User/Controllers/ProfileController.php`), `$this->model->resourceClass::make($request->model)` (Admin `CompanyController::updateCredential`), `$model->resourceClass::collection($result)` (`http/Common/Record/Controllers/IndexController.php`).
- Override only `toArray($request): array` and merge onto the parent: `return array_replace_recursive(parent::toArray($request), [...]);`.
- Attributes via `$this->field`; the model via `$this->resource` (`RoleHelper::routeList($this->resource)` in `RoleResource`).
- Relation-dependent fields only inside `$this->whenLoaded('<relation>', fn() => ..., <default>)` (`CategoryResource`: `full_localized_name` from `ancestors` via `$this->ancestorsPath('localized_name', ' | ')`, helper from `NestedSetResource`; `CategorySearch::with()` always loads `ancestors`). Without the relation the key is omitted; a fallback must be `whenLoaded`'s third argument — `?:` after `whenLoaded()` never fires (`MissingValue` is an object).
- Hide a key: `'<key>' => $this->when(false, null)` (`RoleResource` `pivot`, `NestedSetResource` `descendants`). Mask secrets with a flag: `CredentialResource` `'store' => ['password' => $this->store['password'] !== '']`.
- List vs single: `if (!$this->isCollection) { $this->resource->makeVisible([...]); ... }` before `return parent::toArray($request);` (`RecordActionResource` exposes `old_values` / `new_values` only on show).
- User-facing text via `__()` (`NotificationResource`: `__("user_notifications.$this->type.text", $this->params)`; `RecordActionResource`: `__("fields.$field")`).
- Prepare values in variables before the returned array (`StoreHttpLogResource` `$requestPathType`); private methods only for formatting this resource's data (`RecordActionResource::replaceFieldsWithLabels()`); non-trivial logic → helpers; shared constants may come from the Search class (`StoreHttpLogSearch::REQUEST_PATHS`).
- Non-model data (arrays): `toArray($request): mixed` returning the mapped array (`SettingResource` via `Arr::map`), used as `SettingResource::make(singletons()->system->settings->items)->resolve()` (`http/Common/System/Controllers/SystemController.php`) — the only hard-coded `::make()`.
- Opt-in aggregates belong in Search `show()` (`withCount` / `withAggregate` / `addSelect`); they appear in output automatically.
- Extra data attached by the controller is a plain attribute on the model (`ProfileController`: `loadMissing([...])`, then `$user->all_role_routes = ...` before `make()`).

## Template
```php
<?php

namespace Modules\<Module>\Resources;

use App\Base\Resource;

class <Entity>Resource extends Resource
{
    public function toArray($request): array
    {
        return array_replace_recursive(parent::toArray($request), [
            '<computed_field>' => "$this->last_name $this->first_name",
            '<relation_field>' => $this->whenLoaded('<relation>', fn() => $this-><relation>->name),
            '<hidden_field>' => $this->when(false, null),
        ]);
    }
}
```

Model side:
```php
/**
 * @property int $id
 *
 * @property <Related> $<relation>
 *
 * @property class-string<<Entity>Resource> $resourceClass
 */
class <Entity> extends Model
{
    protected $table = '<table>';

    public string $resourceClass = <Entity>Resource::class;
}
```

## Don't
- Don't build the output field-by-field (`['id' => $this->id, ...]`) — merge onto `parent::toArray()`.
- Don't extend `JsonResource` / `ResourceCollection` directly (only `App\Base\Resource` does).
- Don't wrap relations manually (`OtherResource::make($this->relation)`) — the base does it via each model's `$resourceClass`.
- Don't touch relations outside `whenLoaded()` and don't `load()` / `loadMissing()` in resources — loading belongs to Search `$relations` / `with()` or the controller.
- Don't query, save, lock, `abort()` or throw in resources.
- Don't hard-code `<Entity>Resource::make()` for models — use `$model->resourceClass`.
- Don't create resources in `http/` or `app/` for module models.
