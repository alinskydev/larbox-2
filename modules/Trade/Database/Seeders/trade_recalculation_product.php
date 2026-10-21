<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

return new class() extends Seeder
{
    public function run()
    {
        DB::table('trade_recalculation_product')->insert([
            [
                'creator_id' => 2,
                'company_id' => 1,
                'product_id' => 1,
                'price' => 1000,
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'creator_id' => 2,
                'company_id' => 1,
                'product_id' => 1,
                'price' => 2000,
                'created_at' => date('Y-m-d H:i:s'),
            ],
        ]);
    }
};
