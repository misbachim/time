<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnOnWorksheetRawTimesheetAndTimesheet extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('timesheets', function (Blueprint $table) {
            if (!Schema::hasColumn('timesheets', 'value')) {
                $table->integer('value')->nullable(true);
            }
        });

        Schema::table('raw_timesheets', function (Blueprint $table) {
            if (!Schema::hasColumn('raw_timesheets', 'worksheet_id')) {
                $table->integer('worksheet_id')->nullable(true);
            }
        });

        Schema::table('worksheets', function (Blueprint $table) {
            if (!Schema::hasColumn('raw_timesheets', 'activity_value')) {
                $table->integer('activity_value')->nullable(true);
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
        Schema::table('timesheets', function (Blueprint $table) {
            if (Schema::hasColumn('timesheets', 'value')) {
                $table->dropColumn('value');
            }
        });

        Schema::table('raw_timesheets', function (Blueprint $table) {
            if (Schema::hasColumn('raw_timesheets', 'worksheet_id')) {
                $table->dropColumn('worksheet_id');
            }
        });

        Schema::table('worksheets', function (Blueprint $table) {
            if (Schema::hasColumn('worksheets', 'activity_value')) {
                $table->dropColumn('activity_value');
            }
        });
    }
}
