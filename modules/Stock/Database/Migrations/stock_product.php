<?php

use App\Helpers\Database\DatabaseMigrationIndexHelper;
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
            table: 'stock_product',
            fields: 'company_id',
            tableCallback: function ($table) {
                Schema::create($table, function (Blueprint $table) {
                    $table->id();
                    $table->bigInteger('company_id')->index();
                    $table->bigInteger('stock_id')->index();
                    $table->bigInteger('batch_id')->index();
                    $table->bigInteger('product_id')->index();
                    $table->decimal('purchase_price', 20);
                    $table->decimal('quantity', 20, 3);

                    $table->unique(['company_id', 'batch_id', 'product_id']);
                });
            },
        );

        DatabaseMigrationIndexHelper::createConstraint(
            table: 'stock_product',
            name: 'stock_product_quantity_check',
            definition: 'CHECK (quantity >= 0)',
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stock_product');
    }
};
