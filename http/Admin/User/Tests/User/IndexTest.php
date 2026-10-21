<?php

namespace Http\Admin\User\Tests\User;

use App\Testing\Feature\Traits\SearchFeatureTestTrait;
use Modules\User\Enums\UserTypeEnum;
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
            'type' => UserTypeEnum::ADMIN->value,

            'username_or_phone' => 'admin',
            'full_name' => 'full name',

            'roles.id' => [1, 2],
            'responsible_companies.id' => [1, 2],
        ]);
    }
}
