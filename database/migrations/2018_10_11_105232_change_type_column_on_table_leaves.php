<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeTypeColumnOnTableLeaves extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropColumn('is_annual_leave');
            $table->dropColumn('is_allow_half_day');
            $table->dropColumn('is_annual_leave_deductor');
            $table->dropColumn('is_requestable');
            $table->dropColumn('is_quota_based');
        });

        Schema::table('leaves', function (Blueprint $table) {
            $table->smallInteger('is_annual_leave')->default(1);
            $table->smallInteger('is_allow_half_day')->default(1);
            $table->smallInteger('is_annual_leave_deductor')->default(1);
            $table->smallInteger('is_requestable')->default(1);
            $table->smallInteger('is_quota_based')->default(1);
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropColumn('is_annual_leave');
            $table->dropColumn('is_allow_half_day');
            $table->dropColumn('is_annual_leave_deductor');
            $table->dropColumn('is_requestable');
            $table->dropColumn('is_quota_based');
        });

        Schema::table('leaves', function (Blueprint $table) {
            $table->binary('is_annual_leave')->default(1);
            $table->binary('is_allow_half_day')->default(1);
            $table->binary('is_annual_leave_deductor')->default(1);
            $table->binary('is_requestable')->default(1);
            $table->binary('is_quota_based')->default(1);
        });
    }
}
