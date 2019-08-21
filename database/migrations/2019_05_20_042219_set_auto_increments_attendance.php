<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SetAutoIncrementsAttendance extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropPrimary();
        });
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('id');
        });
        Schema::table('attendances', function (Blueprint $table) {
            $table->increments('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('attendances', function (Blueprint $table) {
            Schema::table('attendances', function (Blueprint $table) {
            $table->dropPrimary();
            });
            Schema::table('attendances', function (Blueprint $table) {
                $table->dropColumn('id');
            });
            Schema::table('attendances', function (Blueprint $table) {
                $table->integer('id')->primary();
            });
        });
    }
}
