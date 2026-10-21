<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Trade\Enums\PurchaseStatusEnum;

return new class() extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trade_purchase', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('creator_id')->index();
            $table->bigInteger('company_id')->index();
            $table->bigInteger('stock_id')->index();
            $table->bigInteger('batch_id')->nullable()->index();
            $table->enum('status', PurchaseStatusEnum::values());
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
        Schema::dropIfExists('trade_purchase');
    }
};
