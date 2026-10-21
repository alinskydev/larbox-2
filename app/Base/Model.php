<?php

namespace App\Base;

use App\Base\Model\ModelRelationsTrait;
use App\Base\Model\ModelSafelyTrait;
use App\Casts\ExternalStorage\AsImage;
use App\Casts\ExternalStorage\AsImages;
use App\Helpers\ClassHelper;
use Illuminate\Database\Eloquent\Model as BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @method static EloquentBuilder<static> query()
 * @method static EloquentBuilder<static> newQuery()
 *
 * @property class-string<Resource> $resourceClass
 */
class Model extends BaseModel
{
    use ModelRelationsTrait;
    use ModelSafelyTrait;

    public string $resourceClass = Resource::class;

    protected array $additionalConnections = [];

    /**
     * @var array{
     *      current_company: string[],
     *      nested_companies: string[],
     *      holding_companies: string[],
     *      administered_companies: string[],
     * }
     */
    protected array $autoFilters = [];

    public bool $shouldHandleDailyLimit = true;
    public bool $shouldSkipAutoFilters = false;

    private static array $classProperties = [];

    public function __construct(array $attributes = [])
    {
        [$this->guarded, $this->hidden, $this->casts, $this->appends] = static::$classProperties[static::class] ??= [
            array_merge(['id', 'created_at', 'updated_at', 'deleted_at'], $this->guarded),
            array_merge(['deleted_at', 'sort_index', 'pivot', 'seo_meta'], $this->hidden),
            array_merge(['created_at' => 'string', 'updated_at' => 'string', 'deleted_at' => 'string'], $this->casts),
            array_merge(ClassHelper::uses($this, SoftDeletes::class) ? ['is_deleted'] : [], $this->appends),
        ];

        parent::__construct($attributes);
    }

    public function getIsDeletedAttribute(): bool
    {
        return (bool)$this->deleted_at;
    }

    public function attributesToArray()
    {
        $attributes = parent::attributesToArray();

        foreach ($this->getCasts() as $key => $cast) {
            $castClass = explode(':', $cast)[0];
            $thumbsKey = "{$key}_thumbs";

            if (in_array($castClass, [AsImage::class, AsImages::class]) && array_key_exists($thumbsKey, $attributes)) {
                $attributes[$thumbsKey] = $this->$thumbsKey;
            }
        }

        return $attributes;
    }

    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format($this->dateFormat ?? 'Y-m-d H:i:s');
    }

    public static function queryWithoutAutoFilters(): EloquentBuilder
    {
        $model = new static();
        $model->shouldSkipAutoFilters = true;

        return $model->newQuery();
    }

    /**
     * @param class-string<EloquentBuilder>|null $builderClass
     */
    public function newEloquentBuilder($query, $builderClass = null)
    {
        $builderClass ??= EloquentBuilder::class;
        return new $builderClass($query);
    }

    public function newQuery()
    {
        $builder = parent::newQuery();

        if ($this->shouldSkipAutoFilters || !$this->autoFilters || !user()) {
            return $builder;
        }

        foreach ($this->autoFilters as $type => $fields) {
            $builder->where(function ($query) use ($type, $fields) {
                foreach ($fields as $field) {
                    $query->orWhere(function ($q) use ($type, $field) {
                        $path = "$this->table.$field";

                        match ($type) {
                            'current_company' => $q->filterByCurrentCompany($path),
                            'nested_companies' => $q->filterByNestedCompanies($path),
                            'holding_companies' => $q->filterByHoldingCompanies($path),
                            'administered_companies' => $q->filterByAdministeredCompanies($path),
                        };
                    });
                }
            });
        }

        return $builder;
    }

    protected function newBaseQueryBuilder()
    {
        $connection = $this->getConnection();
        return new QueryBuilder($connection, $connection->getQueryGrammar(), $connection->getPostProcessor());
    }

    protected function setKeysForSaveQuery($query)
    {
        parent::setKeysForSaveQuery($query);

        if ($companyId = $this->getOriginal('company_id')) {
            $query->where($this->qualifyColumn('company_id'), $companyId);
        }

        return $query;
    }

    public function getService(): ModelService
    {
        return new ModelService($this);
    }

    /**
     * @return array{
     *      daily_limit: int,
     *      sluggable: array{
     *          source: string,
     *          destination: string
     *      },
     *      audit_record_action: array{
     *          excluded: string[],
     *          relations: array{belongsTo: array, oneToMany: array, manyToMany: array},
     *      },
     * }
     */
    public function observerParams(): array
    {
        return [];
    }
}
