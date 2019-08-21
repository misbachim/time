<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;

class ChangeTypeDataTimeStartInWorksheetsToTimeStamp extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worksheets', function (Blueprint $table) {
            $table->dropColumn('time_start');            
        });

        Schema::table('worksheets', function (Blueprint $table) {
            $table->timestampTz('time_start')->default(Carbon::now());
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('worksheets', function (Blueprint $table) {
            $table->dropColumn('time_start');            
        });

        Schema::table('worksheets', function (Blueprint $table) {
            $table->integer('time_start')->default(1);
        });
    }
}
