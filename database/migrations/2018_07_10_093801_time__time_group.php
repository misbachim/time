<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TimeTimeGroup extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
          Schema::create('time_groups', function (Blueprint $table) {
            $table->integer('tenant_id');
            $table->integer('company_id');
            $table->increments('id');
            $table->date('eff_begin');
            $table->date('eff_end');
            $table->string('code', 20)->unique();
            $table->string('name', 50);
            $table->string('description', 255)->nullable(true);
            $table->boolean('is_ignore_holiday');
            $table->boolean('is_flexy_hour');
            $table->boolean('is_allow_overtime');
            $table->boolean('is_flexy_holiday_overtime');
            $table->boolean('is_default');
            $table->boolean('is_absence_as_annual_leave');
            $table->integer('created_by');
            $table->timestampTz('created_at');
            $table->integer('updated_by')->nullable(true);
            $table->timestampTz('updated_at')->nullable(true);
            $table->time('ovt_rounding');
            $table->time('late_tolerance');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
          Schema::dropIfExists('time_groups');
    }
}
