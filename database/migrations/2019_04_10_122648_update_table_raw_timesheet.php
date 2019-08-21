<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateTableRawTimesheet extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('raw_timesheets', function (Blueprint $table) {
            if (!Schema::hasColumn('raw_timesheets', 'clock_time_lat')) {
                $table->double('clock_time_lat', 10, 6)->nullable(true);
            }
            if (!Schema::hasColumn('raw_timesheets', 'clock_time_long')) {
                $table->double('clock_time_long', 10, 6)->nullable(true);
            }

            if (Schema::hasColumn('raw_timesheets', 'location_code')) {
                $table->renameColumn('location_code', 'project_code');
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
        Schema::table('raw_timesheets', function (Blueprint $table) {
            if (Schema::hasColumn('raw_timesheets', 'clock_time_lat')) {
                $table->dropColumn('clock_time_lat');
            }
            if (Schema::hasColumn('raw_timesheets', 'clock_time_long')) {
                $table->dropColumn('clock_time_long');
            }

            if (Schema::hasColumn('raw_timesheets', 'project_code')) {
                $table->renameColumn('project_code', 'location_code');
            }
        });
    }
}
