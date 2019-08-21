<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateTableTimesheet extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('timesheets', function (Blueprint $table) {
            if (!Schema::hasColumn('timesheets', 'time_out_lat')) {
                $table->double('time_out_lat', 10, 6)->nullable(true);
            }
            if (!Schema::hasColumn('timesheets', 'time_out_long')) {
                $table->double('time_out_long', 10, 6)->nullable(true);
            }
            if (!Schema::hasColumn('timesheets', 'time_in_lat')) {
                $table->double('time_in_lat', 10, 6)->nullable(true);
            }
            if (!Schema::hasColumn('timesheets', 'time_in_long')) {
                $table->double('time_in_long', 10, 6)->nullable(true);
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
            if (Schema::hasColumn('timesheets', 'time_out_lat')) {
                $table->dropColumn('time_out_lat');
            }
            if (Schema::hasColumn('timesheets', 'time_out_long')) {
                $table->dropColumn('time_out_long');
            }
            if (Schema::hasColumn('timesheets', 'time_in_lat')) {
                $table->dropColumn('time_in_lat');
            }
            if (Schema::hasColumn('timesheets', 'time_in_long')) {
                $table->dropColumn('time_in_long');
            }
        });
    }
}
