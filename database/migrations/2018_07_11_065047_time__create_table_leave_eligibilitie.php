<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TimeCreateTableLeaveEligibilitie extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('leave_eligibilities', function (Blueprint $table) {
          $table->integer('tenant_id');
          $table->integer('company_id');
          $table->string('leave_code', 20);
          $table->string('lov_lvel', 20);
          $table->string('value', 50);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('leave_eligibilities');
    }
}
