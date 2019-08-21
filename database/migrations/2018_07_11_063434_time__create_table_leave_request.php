<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TimeCreateTableLeaveRequest extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('leave_requests', function (Blueprint $table) {
          $table->integer('tenant_id');
          $table->integer('company_id');
          $table->increments('id');
          $table->string('leave_code', 20);
          $table->string('file_reference', 1000)->nullable(true);
          $table->string('description', 255)->nullable(true);
          $table->char('status', 1);
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
        Schema::dropIfExists('leave_requests');
    }
}
