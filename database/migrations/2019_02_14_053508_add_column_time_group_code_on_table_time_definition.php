<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnTimeGroupCodeOnTableTimeDefinition extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('time_definitions', function (Blueprint $table) {
//            if (!Schema::hasColumn('time_definitions', 'day')) {
//                $table->string('day', 1)->nullable(true);
//            }
            if (!Schema::hasColumn('time_definitions', 'time_group_code')) {
                $table->string('time_group_code', 20)->nullable(true);
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
        Schema::table('time_definitions', function (Blueprint $table) {
//            if (Schema::hasColumn('time_definitions', 'day')) {
//                $table->dropColumn('day');
//            }
            if (Schema::hasColumn('time_definitions', 'time_group_code')) {
                $table->dropColumn('time_group_code');
            }
        });
    }
}
