<?php

namespace Http\Admin\User\Tests\Role;

use App\Testing\Feature\Traits\SearchFeatureTestTrait;
use Modules\User\Enums\UserTypeEnum;
use Modules\User\Search\RoleSearch;

class IndexTest extends _TestCase
{
    use SearchFeatureTestTrait {
        test_show_with_deleted as private;
        test_show_only_deleted as private;
    }

    public string $searchClass = RoleSearch::class;

    public function test_available_filters(): void
    {
        $this->sendRequestWithFilters([
            'id' => 1,
            'creator_id' => 1,
            'company_id' => 1,
            'type' => UserTypeEnum::ADMIN->value,
            'name' => 'admin',
        ]);
    }
}
