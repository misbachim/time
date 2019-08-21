<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TimeAddForeigKeyToTimeGroupSchedule extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('time_group_schedules', function (Blueprint $table) {
          $table->foreign('leave_code')->references('code')->on('leaves');
          $table->foreign('time_group_code')->references('code')->on('time_groups');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('time_group_schedules', function (Blueprint $table) {
          $table->dropForeign('time_group_schedules_leave_code_foreign');
          $table->dropForeign('time_group_schedules_time_group_code_foreign');
        });
    }
}
