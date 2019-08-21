<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TimeTimesheet extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('timesheets', function (Blueprint $table) {
          $table->integer('tenant_id');
          $table->integer('company_id');
          $table->date('date');
          $table->timestamp('schedule_time_in')->nullable(true);
          $table->timestamp('time_in')->nullable(true);
          $table->time('time_in_deviation')->nullable(true);
          $table->timestamp('schedule_time_out')->nullable(true);
          $table->timestamp('time_out')->nullable(true);
          $table->time('time_out_deviation')->nullable(true);
          $table->time('schedule_duration')->nullable(true);
          $table->time('duration')->nullable(true);
          $table->time('duration_deviation')->nullable(true);
          $table->string('attendance_code', 255)->nullable(true);
          $table->string('leave_code', 20)->nullable(true);
          $table->double('leave_weight')->nullable(true);
          $table->time('overtime')->nullable(true);
          $table->time('process_code')->nullable(true);
          $table->integer('created_by');
          $table->timestampTz('created_at');
          $table->boolean('is_work_day');
          $table->boolean('is_flexy_hour');
          $table->string('employee_id', 20)->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('timesheets');
    }
}
