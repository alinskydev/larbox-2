<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\System\Enums\DailyLimitActionTypeEnum;
use Modules\System\Enums\DailyLimitEntityTypeEnum;

return new class() extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('system_daily_limit', function (Blueprint $table) {
            $table->id();
            $table->enum('entity_type', DailyLimitEntityTypeEnum::values());
            $table->bigInteger('entity_id');
            $table->enum('action_type', DailyLimitActionTypeEnum::values());
            $table->string('action_name');
            $table->integer('remaining');

            $table->unique(['entity_type', 'entity_id', 'action_type', 'action_name']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('system_daily_limit');
    }
};
