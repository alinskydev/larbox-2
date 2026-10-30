<?php

use App\Helpers\Database\DatabaseMigrationIndexHelper;
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
        Schema::create('company_subscription', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('creator_id')->index();
            $table->string('name');
            $table->string('description', 1000)->nullable();
            $table->jsonb('record_limits');
            $table->timestamp('created_at')->index();
            $table->timestamp('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('company_subscription');
    }
};
