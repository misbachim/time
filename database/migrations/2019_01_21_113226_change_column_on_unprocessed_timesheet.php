<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeColumnOnUnprocessedTimesheet extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('raw_timesheets', function (Blueprint $table) {
            if (!Schema::hasColumn('raw_timesheets', 'clock_time')) {
                $table->dateTime('clock_time')->nullable();
            }
            if (!Schema::hasColumn('raw_timesheets', 'type')) {
                $table->char('type',1)->default('I');
            }
            if (!Schema::hasColumn('raw_timesheets', 'location_code')) {
                $table->char('location_code',20)->nullable();
            }
            if (!Schema::hasColumn('raw_timesheets', 'id')) {
                $table->increments('id');
            }
        });

        Schema::table('raw_timesheets', function (Blueprint $table) {
            if (Schema::hasColumn('raw_timesheets', 'time_in')) {
                $table->dropColumn('time_in');
            }
            if (Schema::hasColumn('raw_timesheets', 'time_out')) {
                $table->dropColumn('time_out');
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
            if (Schema::hasColumn('raw_timesheets', 'clock_time')) {
                $table->dropColumn('clock_time');
            }
            if (Schema::hasColumn('raw_timesheets', 'type')) {
                $table->dropColumn('type');
            }
            if (Schema::hasColumn('raw_timesheets', 'location_code')) {
                $table->dropColumn('location_code');
            }
            if (Schema::hasColumn('raw_timesheets', 'id')) {
                $table->dropColumn('id');
            }
        });

        Schema::table('raw_timesheets', function (Blueprint $table) {
            if (!Schema::hasColumn('raw_timesheets', 'time_in')) {
                $table->dateTime('time_in')->default('2018-01-01 08:00:00');;
            }
            if (!Schema::hasColumn('raw_timesheets', 'time_out')) {
                $table->dateTime('time_out')->default('2018-01-01 08:00:00');;
            }
        });
    }
}
