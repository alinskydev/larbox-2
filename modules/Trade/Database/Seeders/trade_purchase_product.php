<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

return new class() extends Seeder
{
    public function run()
    {
        DB::table('trade_purchase_product')->insert([

            // Purchase 1 (pending)

            [
                'company_id' => 1,
                'purchase_id' => 1,
                'product_id' => 1,
                'purchase_price' => 900,
                'sale_price' => 1300,
                'quantity' => 5,
            ],
            [
                'company_id' => 1,
                'purchase_id' => 1,
                'product_id' => 2,
                'purchase_price' => 1900,
                'sale_price' => 2600,
                'quantity' => 3,
            ],

            // Purchase 2 (completed)

            [
                'company_id' => 1,
                'purchase_id' => 2,
                'product_id' => 1,
                'purchase_price' => 1000,
                'sale_price' => 1400,
                'quantity' => 1,
            ],
            [
                'company_id' => 1,
                'purchase_id' => 2,
                'product_id' => 2,
                'purchase_price' => 2000,
                'sale_price' => 2800,
                'quantity' => 2,
            ],
        ]);
    }
};
