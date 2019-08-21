<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TimeTimeDefinition extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('time_definitions', function (Blueprint $table) {
            $table->integer('tenant_id');
            $table->integer('company_id');
            $table->increments('id');
            $table->string('code', 20);
            $table->string('name', 50);
            $table->string('description', 255)->nullable(true);
            $table->date('eff_begin');
            $table->date('eff_end');
            $table->char('measurement', 1);
            $table->string('lov_tdevty', 10);
            $table->binary('is_flexy')->default(1);
            $table->binary('is_workday')->default(1);
            $table->string('attendance_codes', 255)->nullable(true);
            $table->string('leave_code', 20)->nullable(true);
            $table->string('lov_tddaty', 10)->nullable(true);
            $table->time('minimum')->nullable(true);
            $table->time('maximum')->nullable(true);
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
        Schema::dropIfExists('time_definitions');
    }
}
