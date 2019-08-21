<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateTableRequestRawTimesheet extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('request_raw_timesheets', function (Blueprint $table) {
            if (!Schema::hasColumn('request_raw_timesheets', 'time_out_lat')) {
                $table->double('time_out_lat', 10, 6)->default(0);
            }
            if (!Schema::hasColumn('request_raw_timesheets', 'time_out_long')) {
                $table->double('time_out_long', 10, 6)->default(0);
            }
            if (!Schema::hasColumn('request_raw_timesheets', 'time_in_lat')) {
                $table->double('time_in_lat', 10, 6)->default(0);
            }
            if (!Schema::hasColumn('request_raw_timesheets', 'time_in_long')) {
                $table->double('time_in_long', 10, 6)->default(0);
            }
            if (Schema::hasColumn('request_raw_timesheets', 'location_code')) {
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
        Schema::table('request_raw_timesheets', function (Blueprint $table) {
            if (Schema::hasColumn('request_raw_timesheets', 'time_out_lat')) {
                $table->dropColumn('time_out_lat');
            }
            if (Schema::hasColumn('request_raw_timesheets', 'time_out_long')) {
                $table->dropColumn('time_out_long');
            }
            if (Schema::hasColumn('request_raw_timesheets', 'time_in_lat')) {
                $table->dropColumn('time_in_lat');
            }
            if (Schema::hasColumn('request_raw_timesheets', 'time_in_long')) {
                $table->dropColumn('time_in_long');
            }
            if (Schema::hasColumn('request_raw_timesheets', 'project_code')) {
                $table->renameColumn('project_code', 'location_code');
            }
        });
    }
}
