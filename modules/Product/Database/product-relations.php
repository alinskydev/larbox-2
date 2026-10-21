<?php

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
        Schema::table('product', function (Blueprint $table) {
            $table->foreign('creator_id')->references('id')->on('user')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('company_id')->references('id')->on('company')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('category_id')
                ->references('id')
                ->on('product_category')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->foreign('brand_id')
                ->references('id')
                ->on('product_brand')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });

        Schema::table('product_brand', function (Blueprint $table) {
            $table->foreign('creator_id')->references('id')->on('user')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('company_id')->references('id')->on('company')->onUpdate('cascade')->onDelete('restrict');
        });

        Schema::table('product_category', function (Blueprint $table) {
            $table->foreign('creator_id')->references('id')->on('user')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('company_id')->references('id')->on('company')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('parent_id')->references('id')->on('product_category')->onUpdate('cascade')->onDelete('restrict');
        });

        Schema::table('product_option', function (Blueprint $table) {
            $table->foreign('creator_id')->references('id')->on('user')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('company_id')->references('id')->on('company')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('specification_id')
                ->references('id')
                ->on('product_specification')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        Schema::table('product_option_ref', function (Blueprint $table) {
            $table->foreign('product_company_id')->references('id')->on('company')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign(['product_company_id', 'product_id'])
                ->references(['company_id', 'id'])
                ->on('product')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });

        Schema::table('product_price', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('company')->onUpdate('cascade')->onDelete('restrict');
        });

        Schema::table('product_specification', function (Blueprint $table) {
            $table->foreign('creator_id')->references('id')->on('user')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('company_id')->references('id')->on('company')->onUpdate('cascade')->onDelete('restrict');
        });
    }
};
