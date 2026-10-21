<?php

use App\Helpers\Database\DatabaseMigrationPartitionHelper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Audit\Enums\RecordActionTypeEnum;

return new class() extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DatabaseMigrationPartitionHelper::createTableWithPartitionsByCompanyIdRanges(
            table: 'audit_record_action',
            fields: 'company_id',
            tableCallback: function ($table) {
                Schema::create($table, function (Blueprint $table) {
                    $table->id();
                    $table->bigInteger('creator_id')->nullable()->index();
                    $table->bigInteger('company_id')->index();
                    $table->morphs('auditable');
                    $table->enum('type', RecordActionTypeEnum::values());
                    $table->jsonb('old_values');
                    $table->jsonb('new_values');
                    $table->timestamp('created_at')->index();
                });
            },
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('audit_record_action');
    }
};
