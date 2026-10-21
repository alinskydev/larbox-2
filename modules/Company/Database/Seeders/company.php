<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

return new class() extends Seeder
{
    public function run()
    {
        DB::table('company')->insert([
            [
                'parent_id' => null,
                'creator_id' => 1,
                'subscription_id' => 1,
                'name' => 'Company 1',
                'logo' => 'test_data/images/1.png',
                'address' => 'Address 1',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'parent_id' => 1,
                'creator_id' => 1,
                'subscription_id' => 1,
                'name' => 'Company 1-1',
                'logo' => 'test_data/images/2.png',
                'address' => 'Address 1-1',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ]);
    }
};
