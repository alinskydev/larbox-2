<?php

use App\Helpers\Database\DatabaseSeederHelper;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

return new class() extends Seeder
{
    public function run()
    {
        DB::table('product_brand')->insert([
            [
                'creator_id' => 2,
                'company_id' => 1,
                'name' => 'Brand 1',
                'slug' => DatabaseSeederHelper::slug('Brand 1'),
                'image' => 'test_data/images/1.png',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'creator_id' => 2,
                'company_id' => 1,
                'name' => 'Brand 2',
                'slug' => DatabaseSeederHelper::slug('Brand 2'),
                'image' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ]);
    }
};
