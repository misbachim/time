<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TimeCreateTableLogSchedule extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('log_schedules', function (Blueprint $table) {
          $table->integer('tenant_id');
          $table->integer('company_id');
          $table->increments('id');
          $table->string('time_group_code', 20);
          $table->date('date_start');
          $table->date('date_end');
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
        Schema::dropIfExists('log_schedules');
    }
}
