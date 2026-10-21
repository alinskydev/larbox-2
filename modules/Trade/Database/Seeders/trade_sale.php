<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Trade\Enums\SaleStatusEnum;

return new class() extends Seeder
{
    public function run()
    {
        DB::table('trade_sale')->insert([
            [
                'creator_id' => 2,
                'company_id' => 1,
                'stock_id' => 1,
                'status' => SaleStatusEnum::COMPLETED->value,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'creator_id' => 2,
                'company_id' => 1,
                'stock_id' => 1,
                'status' => SaleStatusEnum::PARTIALLY_CANCELLED->value,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ]);
    }
};
