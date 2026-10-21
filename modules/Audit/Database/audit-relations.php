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
        Schema::table('audit_error_log', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('user')->onUpdate('cascade')->onDelete('restrict');
        });

        Schema::table('audit_record_action', function (Blueprint $table) {
            $table->foreign('creator_id')->references('id')->on('user')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('company_id')->references('id')->on('company')->onUpdate('cascade')->onDelete('restrict');
        });
    }
};
