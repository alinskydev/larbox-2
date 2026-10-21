<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

return new class() extends Seeder
{
    public function run()
    {
        DB::table('user_role_ref')->insert([
            [
                'user_id' => 1,
                'role_id' => 1,
            ],
            [
                'user_id' => 2,
                'role_id' => 2,
            ],
            [
                'user_id' => 3,
                'role_id' => 3,
            ],
            [
                'user_id' => 4,
                'role_id' => 4,
            ],
            [
                'user_id' => 4,
                'role_id' => 5,
            ],
        ]);
    }
};
