<?php

use App\Helpers\Database\DatabaseSeederHelper;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

return new class() extends Seeder
{
    public function run()
    {
        DB::table('product')->insert([
            [
                'parent_id' => null,
                'creator_id' => 1,
                'company_id' => 1,
                'category_id' => 4,
                'brand_id' => 1,
                'name' => 'Product 1',
                'full_text' => 'Product 1',
                'slug' => DatabaseSeederHelper::slug('Product 1', false),
                'description' => 'Description 1',
                'barcode' => '0000000000001',
                'image_list' => json_encode([
                    'test_data/images/1.png',
                    'test_data/images/2.png',
                ]),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'parent_id' => 1,
                'creator_id' => 1,
                'company_id' => 1,
                'category_id' => 4,
                'brand_id' => 1,
                'name' => 'Product 1-1',
                'full_text' => 'Product 1-1 full text',
                'slug' => DatabaseSeederHelper::slug('Product 1-1', false),
                'description' => 'Description 1-1',
                'barcode' => '0000000000011',
                'image_list' => json_encode([
                    'test_data/images/1.png',
                    'test_data/images/2.png',
                ]),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'parent_id' => null,
                'creator_id' => 2,
                'company_id' => 1,
                'category_id' => 5,
                'brand_id' => 1,
                'name' => 'Product 2',
                'full_text' => 'Product 2',
                'slug' => DatabaseSeederHelper::slug('Product 2', false),
                'description' => 'Description 2',
                'barcode' => '0000000000002',
                'image_list' => json_encode([]),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ]);
    }
};
