<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Trade\Enums\SaleTransactionPaymentTypeEnum;
use Modules\Trade\Enums\SaleTransactionTypeEnum;

return new class() extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trade_sale_transaction', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('company_id')->index();
            $table->bigInteger('sale_id')->index();
            $table->enum('type', SaleTransactionTypeEnum::values());
            $table->enum('payment_type', SaleTransactionPaymentTypeEnum::values());
            $table->decimal('amount', 20);
            $table->timestamp('created_at')->index();
            $table->timestamp('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trade_sale_transaction');
    }
};
