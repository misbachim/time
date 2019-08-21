<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TimeCreateTableAttendance extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendances', function (Blueprint $table) {
          $table->integer('tenant_id');
          $table->integer('company_id');
          $table->integer('id')->primary();
          $table->string('code', 20);
          $table->string('permit_code', 20)->nullable(true);
          $table->string('name', 50);
          $table->string('description', 255)->nullable(true);
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
        Schema::dropIfExists('attendances');
    }
}
