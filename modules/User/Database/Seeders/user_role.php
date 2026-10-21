<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\User\Enums\UserTypeEnum;

return new class() extends Seeder
{
    public function run()
    {
        DB::table('user_role')->insert([
            [
                'creator_id' => 1,
                'company_id' => null,
                'type' => UserTypeEnum::ADMIN->value,
                'name' => 'Admin 1',
                'description' => 'Description 1',
                'routes' => json_encode([
                    'admin.*',
                ]),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'creator_id' => 1,
                'company_id' => null,
                'type' => UserTypeEnum::COMPANY_DIRECTOR->value,
                'name' => 'Director 1',
                'description' => 'Description 1',
                'routes' => json_encode([
                    'company.*',
                ]),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'creator_id' => 1,
                'company_id' => null,
                'type' => UserTypeEnum::COMPANY_DIRECTOR->value,
                'name' => 'Director 1-1',
                'description' => 'Description 1-1',
                'routes' => json_encode([
                    'company.*',
                ]),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'creator_id' => 2,
                'company_id' => 1,
                'type' => UserTypeEnum::COMPANY_EMPLOYEE->value,
                'name' => 'Employee 1',
                'description' => 'Description 1',
                'routes' => json_encode([
                    'company.product.product.index',
                    'company.product.product.show',
                ]),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'creator_id' => 3,
                'company_id' => 1,
                'type' => UserTypeEnum::COMPANY_EMPLOYEE->value,
                'name' => 'Employee 2',
                'description' => 'Description 2',
                'routes' => json_encode([
                    'company.product.product.create',
                    'company.product.product.update',
                ]),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ]);
    }
};
