<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRequestRawTimesheets extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('request_raw_timesheets', function (Blueprint $table) {
            $table->integer('tenant_id');
            $table->integer('company_id');
            $table->increments('id');
            $table->date('date');
            $table->string('location_code',20)->nullable(true);
            $table->dateTime('time_in')->nullable(true);
            $table->dateTime('time_out')->nullable(true);
            $table->string('employee_id', 20);
            $table->integer('value')->nullable(true);
            $table->string('description',255)->default('');
            $table->char('status',1)->default('P');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('request_raw_timesheets');
    }
}
