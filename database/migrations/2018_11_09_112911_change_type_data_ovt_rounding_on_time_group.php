<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeTypeDataOvtRoundingOnTimeGroup extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('time_groups', function (Blueprint $table) {
            DB::statement('ALTER TABLE "time_groups" ALTER COLUMN "ovt_rounding" TYPE interval');
            DB::statement('ALTER TABLE "time_groups" ALTER COLUMN "late_tolerance" TYPE interval');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('time_groups', function (Blueprint $table) {
            DB::statement('ALTER TABLE "time_groups" ALTER COLUMN "ovt_rounding" TYPE time');
            DB::statement('ALTER TABLE "time_groups" ALTER COLUMN "late_tolerance" TYPE time');
        });
    }
}
