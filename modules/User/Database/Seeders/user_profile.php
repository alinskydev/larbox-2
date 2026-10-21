<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

return new class() extends Seeder
{
    public function run()
    {
        DB::table('user_profile')->insert([
            [
                'user_id' => 1,
                'first_name' => null,
                'last_name' => null,
                'position' => null,
            ],
            [
                'user_id' => 2,
                'first_name' => null,
                'last_name' => null,
                'position' => null,
            ],
            [
                'user_id' => 3,
                'first_name' => null,
                'last_name' => null,
                'position' => null,
            ],
            [
                'user_id' => 4,
                'first_name' => 'First name 1',
                'last_name' => 'Last name 1',
                'position' => 'Position 1',
            ],
        ]);
    }
};
