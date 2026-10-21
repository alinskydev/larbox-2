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
        Schema::table('user', function (Blueprint $table) {
            $table->foreign('creator_id')->references('id')->on('user')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('company_id')->references('id')->on('company')->onUpdate('cascade')->onDelete('restrict');
        });

        Schema::table('user_company_responsibility_ref', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('user')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('company')->onUpdate('cascade')->onDelete('cascade');
        });

        Schema::table('user_notification', function (Blueprint $table) {
            $table->foreign('creator_id')->references('id')->on('user')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('recipient_id')->references('id')->on('user')->onUpdate('cascade')->onDelete('restrict');
        });

        Schema::table('user_profile', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('user')->onUpdate('cascade')->onDelete('cascade');
        });

        Schema::table('user_role', function (Blueprint $table) {
            $table->foreign('creator_id')->references('id')->on('user')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign('company_id')->references('id')->on('company')->onUpdate('cascade')->onDelete('restrict');
        });

        Schema::table('user_role_ref', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('user')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('user_role')->onUpdate('cascade')->onDelete('cascade');
        });
    }
};
