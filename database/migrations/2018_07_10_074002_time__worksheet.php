<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TimeWorksheet extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('worksheets', function (Blueprint $table) {
            $table->integer('tenant_id');
            $table->integer('company_id');
            $table->increments('id');
            $table->date('date');
            $table->string('description', 255);
            $table->integer('created_by');
            $table->timestampTz('created_at');
            $table->integer('updated_by')->nullable(true);
            $table->timestampTz('updated_at')->nullable(true);
            $table->string('employee_id', 20);
            $table->integer('time_start');
            $table->timestampTz('time_end');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('worksheets');
    }
}
