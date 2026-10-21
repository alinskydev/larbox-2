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
            table: 'product',
            fields: 'company_id',
            tableCallback: function ($table) {
                Schema::create($table, function (Blueprint $table) {
                    $table->id();
                    $table->bigInteger('parent_id')->nullable()->index();
                    $table->bigInteger('creator_id')->index();
                    $table->bigInteger('company_id')->index();
                    $table->bigInteger('category_id')->index();
                    $table->bigInteger('brand_id')->index();
                    $table->string('name');
                    $table->string('full_text');
                    $table->string('slug')->index();
                    $table->text('description')->nullable();
                    $table->string('barcode')->index();
                    $table->json('image_list')->default('[]');
                    $table->json('image_list_thumbs')->default('[]');
                    $table->timestamp('created_at')->index();
                    $table->timestamp('updated_at');
                    $table->softDeletes();
                });
            },
        );

        DatabaseMigrationIndexHelper::createGinIndex('product', 'full_text');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product');
    }
};
