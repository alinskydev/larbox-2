<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Trade\Enums\SaleTransactionPaymentTypeEnum;
use Modules\Trade\Enums\SaleTransactionTypeEnum;

return new class() extends Seeder
{
    public function run()
    {
        DB::table('trade_sale_transaction')->insert([

            // Sale 1 (completed)

            [
                'company_id' => 1,
                'sale_id' => 1,
                'type' => SaleTransactionTypeEnum::PAYMENT->value,
                'payment_type' => SaleTransactionPaymentTypeEnum::CASH->value,
                'amount' => 1000,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'company_id' => 1,
                'sale_id' => 1,
                'type' => SaleTransactionTypeEnum::PAYMENT->value,
                'payment_type' => SaleTransactionPaymentTypeEnum::CARD->value,
                'amount' => 1800,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],

            // Sale 2 (partially cancelled)

            [
                'company_id' => 1,
                'sale_id' => 2,
                'type' => SaleTransactionTypeEnum::PAYMENT->value,
                'payment_type' => SaleTransactionPaymentTypeEnum::CASH->value,
                'amount' => 4200,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'company_id' => 1,
                'sale_id' => 2,
                'type' => SaleTransactionTypeEnum::REFUND->value,
                'payment_type' => SaleTransactionPaymentTypeEnum::CASH->value,
                'amount' => 2800,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ]);
    }
};
