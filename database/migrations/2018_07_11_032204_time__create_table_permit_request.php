<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TimeCreateTablePermitRequest extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('permit_requests', function (Blueprint $table) {
          $table->integer('tenant_id');
          $table->integer('company_id');
          $table->increments('id');
          $table->string('permit_code', 20);
          $table->string('reason', 50);
          $table->string('file_reference', 1000)->nullable(true);
          $table->date('date');
          $table->char('status', 1);
          $table->integer('created_by');
          $table->timestampTz('created_at');
          $table->integer('updated_by')->nullable(true);
          $table->timestampTz('updated_at')->nullable(true);
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
        Schema::dropIfExists('permit_requests');
    }
}
