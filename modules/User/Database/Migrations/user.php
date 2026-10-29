<?php

use App\Helpers\Database\DatabaseMigrationIndexHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\User\Enums\UserTypeEnum;

return new class() extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('creator_id')->index();
            $table->bigInteger('company_id')->nullable()->index();
            $table->enum('type', UserTypeEnum::values());
            $table->string('username')->unique();
            $table->string('password');
            $table->string('phone', 20)->nullable()->index();
            $table->boolean('is_excel_import_running')->default(false);
            $table->timestamp('created_at')->index();
            $table->timestamp('updated_at');
            $table->softDeletes();
        });

        DatabaseMigrationIndexHelper::createLowerIndex('user', 'username');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user');
    }
};
