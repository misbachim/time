<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TimeCreateTableScheduleException extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('schedule_exceptions', function (Blueprint $table) {
            $table->integer('tenant_id');
            $table->integer('company_id');
            $table->increments('id');
            $table->date('date');
            $table->string('leave_code', 10);
            $table->timestamp('time_in')->nullable(true);
            $table->timestamp('time_out')->nullable(true);
            $table->timestamp('break_start')->nullable(true);
            $table->timestamp('break_end')->nullable(true);
            $table->string('reason');
            $table->integer('created_by');
            $table->timestampTz('created_at');
            $table->integer('updated_by')->nullable(true);
            $table->timestampTz('updated_at')->nullable(true);
            $table->string('employee_id', 20);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('schedule_exceptions');
    }
}
