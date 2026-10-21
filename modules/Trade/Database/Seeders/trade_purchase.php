<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Trade\Enums\PurchaseStatusEnum;

return new class() extends Seeder
{
    public function run()
    {
        DB::table('trade_purchase')->insert([
            [
                'creator_id' => 2,
                'company_id' => 1,
                'stock_id' => 1,
                'batch_id' => null,
                'status' => PurchaseStatusEnum::PENDING->value,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'creator_id' => 2,
                'company_id' => 1,
                'stock_id' => 1,
                'batch_id' => 1,
                'status' => PurchaseStatusEnum::COMPLETED->value,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ]);
    }
};
