<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

return new class() extends Seeder
{
    public function run()
    {
        DB::table('product_price')->insert([
            [
                'company_id' => 1,
                'product_id' => 1,
                'price' => 1000,
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'company_id' => 1,
                'product_id' => 2,
                'price' => 2000,
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'company_id' => 1,
                'product_id' => 3,
                'price' => 3000,
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ]);
    }
};
