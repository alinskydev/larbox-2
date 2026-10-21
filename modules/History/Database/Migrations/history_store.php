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
        Schema::connection('history')->dropIfExists('history_store');

        Schema::connection('history')->create('history_store', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('creator_id')->index();
            $table->bigInteger('company_id')->index();
            $table->string('request_path')->index();
            $table->jsonb('request_body');
            $table->jsonb('response_body');
            $table->smallInteger('response_status');
            $table->jsonb('additional')->default('[]');
            $table->string('ip')->nullable();
            $table->timestamp('created_at')->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('history')->dropIfExists('history_store');
    }
};
