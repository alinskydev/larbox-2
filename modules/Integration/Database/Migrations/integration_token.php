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
        Schema::create('integration_token', function (Blueprint $table) {
            $table->id();
            $table->string('service_name');
            $table->string('username');
            $table->text('token');
            $table->timestamp('created_at')->index();

            $table->unique(['service_name', 'username']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('integration_token');
    }
};
