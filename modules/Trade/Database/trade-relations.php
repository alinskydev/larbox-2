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
        Schema::table('trade_purchase', function (Blueprint $table) {
            $table->foreign('creator_id')->references('id')->on('user')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('company_id')->references('id')->on('company')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('stock_id')->references('id')->on('stock')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('batch_id')->references('id')->on('stock_batch')->onUpdate('cascade')->onDelete('restrict');
        });

        Schema::table('trade_purchase_product', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('company')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('purchase_id')->references('id')->on('trade_purchase')->onUpdate('cascade')->onDelete('restrict');
        });

        Schema::table('trade_recalculation_product', function (Blueprint $table) {
            $table->foreign('creator_id')->references('id')->on('user')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('company_id')->references('id')->on('company')->onUpdate('cascade')->onDelete('restrict');
        });

        Schema::table('trade_sale', function (Blueprint $table) {
            $table->foreign('creator_id')->references('id')->on('user')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('company_id')->references('id')->on('company')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('stock_id')->references('id')->on('stock')->onUpdate('cascade')->onDelete('restrict');
        });

        Schema::table('trade_sale_product', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('company')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('sale_id')->references('id')->on('trade_sale')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('batch_id')->references('id')->on('stock_batch')->onUpdate('cascade')->onDelete('restrict');
        });

        Schema::table('trade_sale_transaction', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('company')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('sale_id')->references('id')->on('trade_sale')->onUpdate('cascade')->onDelete('restrict');
        });
    }
};
