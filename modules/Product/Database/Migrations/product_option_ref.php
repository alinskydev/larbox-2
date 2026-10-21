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
            table: 'product_option_ref',
            fields: 'product_company_id',
            tableCallback: function ($table) {
                Schema::create($table, function (Blueprint $table) {
                    $table->bigInteger('product_company_id');
                    $table->bigInteger('product_id');
                    $table->bigInteger('option_id')->index();

                    $table->primary(['product_company_id', 'product_id', 'option_id']);
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
        Schema::dropIfExists('product_option_ref');
    }
};
