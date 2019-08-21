<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TimeCreateTableOverRequest extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('overtime_requests', function (Blueprint $table) {
            $table->integer('tenant_id');
            $table->integer('company_id');
            $table->increments('id');
            $table->date('schedule_date');
            $table->string('description', 255)->nullable(true);
            $table->char('status', 1);
            $table->integer('created_by');
            $table->timestampTz('created_at');
            $table->integer('updated_by')->nullable(true);
            $table->timestampTz('updated_at')->nullable(true);
            $table->timestamp('time_start');
            $table->timestamp('time_end')->nullable(true);
            $table->string('employee_id', 20)->nullable(true);
            $table->string('file_reference', 1000)->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('overtime_requests');
    }
}
