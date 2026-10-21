<?php

use App\Helpers\Database\DatabaseMigrationPartitionHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DatabaseMigrationPartitionHelper::createTableWithPartitionsByCompanyIdRanges(
            table: 'trade_purchase_product',
            fields: 'company_id',
            tableCallback: function ($table) {
                Schema::create($table, function (Blueprint $table) {
                    $table->id();
                    $table->bigInteger('company_id')->index();
                    $table->bigInteger('purchase_id')->index();
                    $table->bigInteger('product_id')->index();
                    $table->decimal('purchase_price', 20);
                    $table->decimal('sale_price', 20);
                    $table->decimal('quantity', 20, 3);

                    $table->unique(['company_id', 'purchase_id', 'product_id']);
                });
            },
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trade_purchase_product');
    }
};
