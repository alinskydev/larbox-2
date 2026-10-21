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
        Schema::table('company', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('company')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('creator_id')->references('id')->on('user')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('subscription_id')
                ->references('id')
                ->on('company_subscription')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });

        Schema::table('company_credential', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('company')->onUpdate('cascade')->onDelete('cascade');
        });

        Schema::table('company_subscription', function (Blueprint $table) {
            $table->foreign('creator_id')->references('id')->on('user')->onUpdate('cascade')->onDelete('restrict');
        });
    }
};
