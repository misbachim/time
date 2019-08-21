<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeDataTypeMaxQuotaInEmployeeQuotas extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_quotas', function (Blueprint $table) {
            DB::statement('ALTER TABLE "employee_quotas" ALTER COLUMN "max_quota" TYPE float');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_quotas', function (Blueprint $table) {
            DB::statement('ALTER TABLE "employee_quotas" ALTER COLUMN "max_quota" TYPE smallint');
        });
    }
}
