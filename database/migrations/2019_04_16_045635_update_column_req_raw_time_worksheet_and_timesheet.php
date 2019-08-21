<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateColumnReqRawTimeWorksheetAndTimesheet extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('request_raw_timesheets', function (Blueprint $table) {
            if (Schema::hasColumn('request_raw_timesheets', 'value')) {
                $table->renameColumn('value', 'value_1');
            }

            if (!Schema::hasColumn('request_raw_timesheets', 'value_2')) {
                $table->integer('value_2')->nullable(true);
            }
        });

        Schema::table('timesheets', function (Blueprint $table) {
            if (Schema::hasColumn('timesheets', 'value')) {
                $table->renameColumn('value', 'value_1');
            }
            if (!Schema::hasColumn('timesheets', 'value_2')) {
                $table->integer('value_2')->nullable(true);
            }
        });

        Schema::table('worksheets', function (Blueprint $table) {
            if (Schema::hasColumn('worksheets', 'activity_value')) {
                $table->renameColumn('activity_value', 'activity_value_1');
            }
            if (!Schema::hasColumn('worksheets', 'activity_value_2')) {
                $table->integer('activity_value_2')->nullable(true);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('request_raw_timesheets', function (Blueprint $table) {
            if (Schema::hasColumn('request_raw_timesheets', 'value_1')) {
                $table->renameColumn('value_1', 'value');
            }

            if (Schema::hasColumn('request_raw_timesheets', 'value_2')) {
                $table->dropColumn('value_2');
            }
        });

        Schema::table('timesheets', function (Blueprint $table) {
            if (Schema::hasColumn('timesheets', 'value_1')) {
                $table->renameColumn('value_1', 'value');
            }
            if (Schema::hasColumn('timesheets', 'value_2')) {
                $table->dropColumn('value_2');
            }
        });

        Schema::table('worksheets', function (Blueprint $table) {
            if (Schema::hasColumn('worksheets', 'activity_value_1')) {
                $table->renameColumn('activity_value_1', 'activity_value');
            }
            if (Schema::hasColumn('worksheets', 'activity_value_2')) {
                $table->dropColumn('activity_value_2');
            }
        });
    }
}
