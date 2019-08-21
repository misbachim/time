<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TimeCreateTableEmployeeQuotas extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_quotas', function (Blueprint $table) {
          $table->integer('tenant_id');
          $table->integer('company_id');
          $table->string('leave_code', 20);
          $table->date('eff_begin', 20);
          $table->date('eff_end', 20);
          $table->smallInteger('max_quota');
          $table->smallInteger('carried_quota');
          $table->integer('created_by');
          $table->timestampTz('created_at');
          $table->integer('update_by')->nullable(true);
          $table->timestampTz('update_at')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_quotas');
    }
}
