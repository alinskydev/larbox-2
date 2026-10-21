<?php

namespace App\Search;

use App\Base\EloquentBuilder;
use App\Search\Enums\SearchSortTypeEnum;
use Illuminate\Database\Eloquent\Builder;

class SearchSort
{
    /**
     * @param EloquentBuilder $query
     */
    public function __construct(
        private Builder $query,
        private array $sorts,
        private array $params,
    ) {}

    public function process(): void
    {
        $locale = app()->getLocale();

        foreach ($this->params as $param) {
            if (str_starts_with($param, '-')) {
                $direction = 'DESC';
                $param = substr($param, 1);
            } else {
                $direction = 'ASC';
            }

            if (!isset($this->sorts[$param])) continue;

            match ($this->sorts[$param]) {
                SearchSortTypeEnum::RAW => $this->query->orderBy($param, $direction),
                SearchSortTypeEnum::LOCALIZED => $this->query->orderBy("$param->$locale", $direction),
            };
        }

        $this->query->orderBy($this->query->getModel()->getQualifiedKeyName());
    }
}
