<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeTypeDataIsFullDayToEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('events')->truncate();
        DB::table('event_eligibilities')->truncate();
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('is_full_day');
        });
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('is_full_day');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('events')->truncate();
        DB::table('event_eligibilities')->truncate();
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('is_full_day');
        });
        Schema::table('events', function (Blueprint $table) {
            $table->smallInteger('is_full_day');
        });
    }
}