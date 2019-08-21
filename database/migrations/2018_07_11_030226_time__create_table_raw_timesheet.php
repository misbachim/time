<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TimeCreateTableRawTimesheet extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('raw_timesheets', function (Blueprint $table) {
          $table->integer('tenant_id');
          $table->integer('company_id');
          $table->date('date');
          $table->timestamp('time_in')->nullable(true);
          $table->timestamp('time_out')->nullable(true);
          $table->integer('created_by');
          $table->timestampTz('created_at');
          $table->integer('updated_by')->nullable(true);
          $table->timestampTz('updated_at')->nullable(true);
          $table->boolean('is_analyzed');
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
        Schema::dropIfExists('raw_timesheets');
    }
}
