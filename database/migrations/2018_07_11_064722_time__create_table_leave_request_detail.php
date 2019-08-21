<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TimeCreateTableLeaveRequestDetail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('leave_request_details', function (Blueprint $table) {
          $table->integer('tenant_id');
          $table->integer('company_id');
          $table->integer('leave_request_id');
          $table->date('date');
          $table->double('weight');
          $table->char('status', 1)->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('leave_request_details');
    }
}
