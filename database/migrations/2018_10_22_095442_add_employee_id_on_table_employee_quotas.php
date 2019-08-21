<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEmployeeIdOnTableEmployeeQuotas extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_quotas', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_quotas', 'employee_id')) {
                $table->string('employee_id', 20);
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
        Schema::table('employee_quotas', function (Blueprint $table) {
            //
        });
    }
}
