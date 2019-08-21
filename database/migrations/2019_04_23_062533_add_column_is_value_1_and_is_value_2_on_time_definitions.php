<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnIsValue1AndIsValue2OnTimeDefinitions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('time_definitions', function (Blueprint $table) {
            if (!Schema::hasColumn('time_definitions', 'is_value_1')) {
                $table->boolean('is_value_1')->default(false);
            }
            if (!Schema::hasColumn('time_definitions', 'is_value_2')) {
                $table->boolean('is_value_2')->default(false);
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
            if (Schema::hasColumn('time_definitions', 'is_value_1')) {
                $table->dropColumn('is_value_1');
            }
            if (Schema::hasColumn('time_definitions', 'is_value_2')) {
                $table->dropColumn('is_value_2');
            }
        });
    }
}
