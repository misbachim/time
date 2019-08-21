<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnDescription2OnTableWorksheetAndReqTimesheet extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('request_raw_timesheets', function (Blueprint $table) {
            if (!Schema::hasColumn('request_raw_timesheets', 'description_2')) {
                $table->string('description_2',255)->nullable(true);
            }
        });

        Schema::table('worksheets', function (Blueprint $table) {
            if (!Schema::hasColumn('worksheets', 'description_2')) {
                $table->string('description_2',255)->nullable(true);
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
        Schema::table('request_raw_timesheets', function (Blueprint $table) {
            if (Schema::hasColumn('request_raw_timesheets', 'description_2')) {
                $table->dropColumn('description_2');
            }
        });

        Schema::table('worksheets', function (Blueprint $table) {
            if (Schema::hasColumn('worksheets', 'description_2')) {
                $table->dropColumn('description_2');
            }
        });
    }
}