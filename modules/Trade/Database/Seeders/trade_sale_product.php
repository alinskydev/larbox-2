<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

return new class() extends Seeder
{
    public function run()
    {
        DB::table('trade_sale_product')->insert([

            // Sale 1 (completed, FIFO from batches 1 and 2)

            [
                'company_id' => 1,
                'sale_id' => 1,
                'batch_id' => 1,
                'product_id' => 1,
                'purchase_price' => 1000,
                'sale_price' => 1400,
                'sold_quantity' => 1,
                'returned_quantity' => 0,
                'product_snapshot' => json_encode([
                    'id' => 1,
                    'name' => 'Product 1',
                    'barcode' => '0000000000001',
                ]),
            ],
            [
                'company_id' => 1,
                'sale_id' => 1,
                'batch_id' => 2,
                'product_id' => 1,
                'purchase_price' => 1200,
                'sale_price' => 1400,
                'sold_quantity' => 1,
                'returned_quantity' => 0,
                'product_snapshot' => json_encode([
                    'id' => 1,
                    'name' => 'Product 1',
                    'barcode' => '0000000000001',
                ]),
            ],

            // Sale 2 (partially cancelled)

            [
                'company_id' => 1,
                'sale_id' => 2,
                'batch_id' => 1,
                'product_id' => 1,
                'purchase_price' => 1000,
                'sale_price' => 1400,
                'sold_quantity' => 1,
                'returned_quantity' => 0,
                'product_snapshot' => json_encode([
                    'id' => 1,
                    'name' => 'Product 1',
                    'barcode' => '0000000000001',
                ]),
            ],
            [
                'company_id' => 1,
                'sale_id' => 2,
                'batch_id' => 1,
                'product_id' => 2,
                'purchase_price' => 2000,
                'sale_price' => 2800,
                'sold_quantity' => 1,
                'returned_quantity' => 1,
                'product_snapshot' => json_encode([
                    'id' => 2,
                    'name' => 'Product 1-1',
                    'barcode' => '0000000000011',
                ]),
            ],
        ]);
    }
};
