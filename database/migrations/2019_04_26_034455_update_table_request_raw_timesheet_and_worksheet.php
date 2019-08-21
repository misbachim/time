<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateTableRequestRawTimesheetAndWorksheet extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('request_raw_timesheets', function (Blueprint $table) {
            if (!Schema::hasColumn('request_raw_timesheets', 'activity_code')) {
                $table->string('activity_code',20)->nullable(true);
            }
        });

        Schema::table('worksheets', function (Blueprint $table) {
            if (!Schema::hasColumn('worksheets', 'activity_code')) {
                $table->string('activity_code',20)->nullable(true);
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
            if (Schema::hasColumn('request_raw_timesheets', 'activity_code')) {
                $table->dropColumn('activity_code');
            }
        });

        Schema::table('worksheets', function (Blueprint $table) {
            if (Schema::hasColumn('worksheets', 'activity_code')) {
                $table->dropColumn('activity_code');
            }
        });
    }
}
