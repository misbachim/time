<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnQuotaTypeAndQuotaValueOnTableLeave extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('leaves', function (Blueprint $table) {
            if (!Schema::hasColumn('leaves', 'quota_type')) {
                $table->char('quota_type',2)->nullable(true);
            }
            if (!Schema::hasColumn('leaves', 'quota_value')) {
                $table->string('quota_value',20)->nullable(true);
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
        Schema::table('leaves', function (Blueprint $table) {
            if (Schema::hasColumn('leaves', 'quota_type')) {
                $table->dropColumn('quota_type');
            }
            if (Schema::hasColumn('leaves', 'quota_value')) {
                $table->dropColumn('quota_value');
            }
        });
    }
}
