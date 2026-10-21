<?php

namespace App\Testing\Feature\Traits;

use App\Search\Search;

trait SearchFeatureTestTrait
{
    protected Search $search;

    public function test_available_sorts(): void
    {
        $this->search = new ($this->searchClass)();

        $sorts = array_keys($this->search->sorts);
        $sorts = array_map(fn($value) => [$value, "-$value"], $sorts);
        $sorts = array_merge(...$sorts);

        $this->sendRequest(
            query: ['sort' => $sorts],
        );
    }

    public function test_available_relations(): void
    {
        $this->search = new ($this->searchClass)();

        $this->sendRequest(
            query: ['with' => $this->search->relations],
        );
    }

    public function test_show_with_deleted(): void
    {
        $this->sendRequest(
            query: ['show' => ['with_deleted']],
        );
    }

    public function test_show_only_deleted(): void
    {
        $this->sendRequest(
            query: ['show' => ['only_deleted']],
        );
    }

    public function test_pagination(): void
    {
        $this->search = new ($this->searchClass)();

        $this->sendRequest(
            query: [
                'page_size' => $this->search->pageSize,
                'page' => 1,
            ],
        );
    }

    private function sendRequestWithFilters(array $params): void
    {
        $this->sendRequest(
            query: ['filter' => $params],
        );
    }
}
