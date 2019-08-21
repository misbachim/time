<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeTypeDataIsFlexyOnTimeDefinition extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('time_definitions', function (Blueprint $table) {
            $table->dropColumn('is_flexy');
            $table->dropColumn('is_workday');
        });

        Schema::table('time_definitions', function (Blueprint $table) {
            $table->boolean('is_flexy')->default(false);
            $table->boolean('is_workday')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('time_definitions', function (Blueprint $table) {
            $table->dropColumn('is_flexy');
            $table->dropColumn('is_workday');
        });

        Schema::table('time_definitions', function (Blueprint $table) {
            $table->smallInteger('is_flexy')->default(1);
            $table->smallInteger('is_workday')->default(1);
        });
    }
}
