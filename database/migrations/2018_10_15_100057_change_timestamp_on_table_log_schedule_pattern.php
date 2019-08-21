<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeTimestampOnTableLogSchedulePattern extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('log_schedule_patterns', function (Blueprint $table) {
            DB::statement('ALTER TABLE "log_schedule_patterns" ALTER COLUMN "work_start" TYPE time');
            DB::statement('ALTER TABLE "log_schedule_patterns" ALTER COLUMN "break_start" TYPE time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('log_schedule_patterns', function (Blueprint $table) {
            DB::statement('ALTER TABLE "log_schedule_patterns" ALTER COLUMN "work_duration" TYPE timestamp');
            DB::statement('ALTER TABLE "log_schedule_patterns" ALTER COLUMN "break_duration" TYPE timestamp');
        });
    }
}
