<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeDescToNullableOnTableReqRawTimesheet extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('request_raw_timesheets', function (Blueprint $table) {
            if (Schema::hasColumn('request_raw_timesheets', 'description')) {
                $table->string('description',255)->nullable()->change();
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
            if (Schema::hasColumn('request_raw_timesheets', 'description')) {
                $table->string('description',255)->default('');
            }
        });
    }
}
