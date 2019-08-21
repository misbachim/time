<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TimeCreateTableEvent extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('events', function (Blueprint $table) {
          $table->integer('tenant_id');
          $table->integer('company_id');
          $table->increments('id');
          $table->string('leave_code', 20)->nullable(true);
          $table->binary('is_full_day')->default(1);
          $table->string('name', 50);
          $table->timestamp('event_start');
          $table->timestamp('event_end')->nullable(true);
          $table->string('location', 255)->nullable(true);
          $table->string('description', 255)->nullable(true);
          $table->string('color', 10);
          $table->integer('created_by');
          $table->timestampTz('created_at');
          $table->integer('updated_by')->nullable(true);
          $table->timestampTz('updated_at')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('events');
    }
}
