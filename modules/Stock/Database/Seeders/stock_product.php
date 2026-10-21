<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

return new class() extends Seeder
{
    public function run()
    {
        DB::table('stock_product')->insert([

            // Batch 1

            [
                'company_id' => 1,
                'stock_id' => 1,
                'batch_id' => 1,
                'product_id' => 1,
                'purchase_price' => 1000,
                'quantity' => 1,
            ],
            [
                'company_id' => 1,
                'stock_id' => 1,
                'batch_id' => 1,
                'product_id' => 2,
                'purchase_price' => 2000,
                'quantity' => 2,
            ],

            // Batch 2

            [
                'company_id' => 1,
                'stock_id' => 1,
                'batch_id' => 2,
                'product_id' => 1,
                'purchase_price' => 1200,
                'quantity' => 1.2,
            ],
        ]);
    }
};
