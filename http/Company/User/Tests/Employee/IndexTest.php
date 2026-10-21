<?php

namespace Http\Company\User\Tests\Employee;

use App\Testing\Feature\Traits\SearchFeatureTestTrait;
use Modules\User\Search\UserSearch;

class IndexTest extends _TestCase
{
    use SearchFeatureTestTrait;

    public string $searchClass = UserSearch::class;

    public function test_available_filters(): void
    {
        $this->sendRequestWithFilters([
            'id' => 1,
            'creator_id' => 1,
            'company_id' => 1,

            'username_or_phone' => 'employee',
            'full_name' => 'full name',

            'roles.id' => [1, 2],
        ]);
    }
}
