<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TimeCreateTableLogSchedulePattern extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('log_schedule_patterns', function (Blueprint $table) {
            $table->integer('tenant_id');
            $table->integer('company_id');
            $table->integer('log_schedule_id');
            $table->string('leave_code', 20)->nullable(true);
            $table->timestamp('work_start')->nullable(true);
            $table->timestamp('break_start')->nullable(true);
            $table->smallInteger('sequence');
            $table->time('work_duration')->nullable(true);
            $table->time('break_duration')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('log_schedule_patterns');
    }
}
