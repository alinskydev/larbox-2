<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\User\Enums\UserTypeEnum;

return new class() extends Seeder
{
    public function run()
    {
        DB::table('user')->insert([
            [
                'creator_id' => 1,
                'company_id' => null,
                'type' => UserTypeEnum::ADMIN->value,
                'username' => 'admin@local.host',
                'password' => Hash::make('F1VnGPPyPUIx5vklNRFkxRlvZKKbJaCe'),
                'phone' => '998010000001',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'creator_id' => 1,
                'company_id' => 1,
                'type' => UserTypeEnum::COMPANY_DIRECTOR->value,
                'username' => 'director_1@local.host',
                'password' => Hash::make('User_1234'),
                'phone' => '998020000001',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'creator_id' => 1,
                'company_id' => 2,
                'type' => UserTypeEnum::COMPANY_DIRECTOR->value,
                'username' => 'director_1_1@local.host',
                'password' => Hash::make('User_1234'),
                'phone' => '998020000002',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'creator_id' => 2,
                'company_id' => 1,
                'type' => UserTypeEnum::COMPANY_EMPLOYEE->value,
                'username' => 'employee_1@local.host',
                'password' => Hash::make('User_1234'),
                'phone' => '998030000001',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ]);
    }
};
