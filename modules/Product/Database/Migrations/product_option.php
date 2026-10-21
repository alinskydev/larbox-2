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
            table: 'product_option',
            fields: 'company_id',
            tableCallback: function ($table) {
                Schema::create($table, function (Blueprint $table) {
                    $table->id();
                    $table->bigInteger('creator_id')->index();
                    $table->bigInteger('company_id')->index();
                    $table->bigInteger('specification_id')->index();
                    $table->jsonb('name');
                    $table->timestamp('created_at')->index();
                    $table->timestamp('updated_at')->index();
                });
            },
        );

        DatabaseMigrationIndexHelper::createLocalizedLowerIndex('product_option', 'name', 'company_id');
        DatabaseMigrationIndexHelper::createLocalizedGinIndex('product_option', 'name');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_option');
    }
};
