<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TimeLeave extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('leaves', function (Blueprint $table) {
            $table->integer('tenant_id');
            $table->integer('company_id');
            $table->integer('id', true, true);
            $table->char('type', 2);
            $table->string('code', 20);
            $table->string('name', 50);
            $table->string('description', 255)->nullable(true);
            $table->binary('is_annual_leave')->default(1);
            $table->binary('is_allow_half_day')->default(1);
            $table->binary('is_annual_leave_deductor')->default(1);
            $table->smallInteger('max_quota')->nullable(true);
            $table->smallInteger('quota_expiration')->nullable(true);
            $table->smallInteger('day_taken_min')->nullable(true);
            $table->smallInteger('day_taken_max')->nullable(true);
            $table->smallInteger('carry_max')->nullable(true);
            $table->smallInteger('carry_expiration_day')->nullable(true);
            $table->string('lov_lcty', 10)->nullable(true);
            $table->string('lov_lcpt', 10)->nullable(true);
            $table->integer('created_by');
            $table->timestampTz('created_at');
            $table->integer('updated_by')->nullable(true);
            $table->timestampTz('updated_at')->nullable(true);
            $table->binary('is_requestable')->default(1);
            $table->binary('is_quota_based')->default(1);
            $table->dropPrimary('leaves_pkey');
            $table->primary(array('code'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('leaves');
    }
}
