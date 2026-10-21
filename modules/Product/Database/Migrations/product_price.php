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
            table: 'product_price',
            fields: 'company_id',
            tableCallback: function ($table) {
                Schema::create($table, function (Blueprint $table) {
                    $table->id();
                    $table->bigInteger('company_id')->index();
                    $table->bigInteger('product_id')->index();
                    $table->decimal('price', 20);
                    $table->timestamp('updated_at');

                    $table->unique(['company_id', 'product_id']);
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
        Schema::dropIfExists('product_price');
    }
};
