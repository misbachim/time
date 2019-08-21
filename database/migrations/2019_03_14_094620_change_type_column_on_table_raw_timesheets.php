<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeTypeColumnOnTableRawTimesheets extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('raw_timesheets', function (Blueprint $table) {
            if (Schema::hasColumn('raw_timesheets', 'location_code')) {
                $table->dropColumn('location_code');
            }
        });

        Schema::table('raw_timesheets', function (Blueprint $table) {
            if (!Schema::hasColumn('raw_timesheets', 'location_code')) {
                $table->string('location_code',20)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('raw_timesheets', function (Blueprint $table) {
            if (Schema::hasColumn('raw_timesheets', 'location_code')) {
                $table->dropColumn('location_code');
            }
        });

        Schema::table('raw_timesheets', function (Blueprint $table) {
            if (!Schema::hasColumn('raw_timesheets', 'location_code')) {
                $table->char('location_code',20)->nullable();
            }
        });
    }
}
