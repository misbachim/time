<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TimeTimeGroupSchedule extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
          Schema::create('time_group_schedules', function (Blueprint $table) {
            $table->integer('tenant_id');
            $table->integer('company_id');
            $table->string('time_group_code', 20)->unsigned();
            $table->date('date');
            $table->string('leave_code')->nullable(true)->unsigned();
            $table->timestampTz('time_in')->nullable(true);
            $table->timestampTz('time_out')->nullable(true);
            $table->timestampTz('break_start')->nullable(true);
            $table->timestampTz('break_end')->nullable(true);
            $table->integer('created_by');
            $table->timestampTz('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
          Schema::dropIfExists('time_group_schedules');
    }
}
