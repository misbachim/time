<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeTypeColumnTableLogSchedulePattern extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('log_schedule_patterns', function (Blueprint $table) {
            DB::statement('ALTER TABLE "log_schedule_patterns" ALTER COLUMN "work_duration" TYPE interval');
            DB::statement('ALTER TABLE "log_schedule_patterns" ALTER COLUMN "break_duration" TYPE interval');
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
            DB::statement('ALTER TABLE "log_schedule_patterns" ALTER COLUMN "work_duration" TYPE time');
            DB::statement('ALTER TABLE "log_schedule_patterns" ALTER COLUMN "break_duration" TYPE time');
        });
    }
}
