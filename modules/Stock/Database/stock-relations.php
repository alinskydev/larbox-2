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
        Schema::table('stock', function (Blueprint $table) {
            $table->foreign('creator_id')->references('id')->on('user')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('company_id')->references('id')->on('company')->onUpdate('cascade')->onDelete('restrict');
        });

        Schema::table('stock_batch', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('company')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('stock_id')->references('id')->on('stock')->onUpdate('cascade')->onDelete('restrict');
        });

        Schema::table('stock_product', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('company')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('stock_id')->references('id')->on('stock')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('batch_id')->references('id')->on('stock_batch')->onUpdate('cascade')->onDelete('restrict');
        });
    }
};
