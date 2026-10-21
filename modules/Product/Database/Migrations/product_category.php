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
        Schema::create('product_category', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('creator_id')->index();
            $table->bigInteger('company_id')->index();
            $table->bigInteger('parent_id')->nullable();
            $table->bigInteger('lft');
            $table->bigInteger('rgt');
            $table->jsonb('name');
            $table->string('slug')->index();
            $table->timestamp('created_at')->index();
            $table->timestamp('updated_at');
            $table->softDeletes();

            $table->index(['parent_id', 'lft', 'rgt']);
        });

        DatabaseMigrationIndexHelper::createLocalizedLowerIndex('product_category', 'name', 'company_id');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_category');
    }
};
