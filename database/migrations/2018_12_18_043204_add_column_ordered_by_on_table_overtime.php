<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnOrderedByOnTableOvertime extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('overtime_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('overtime_requests', 'ordered_by')) {
                $table->string('ordered_by', 20)->nullable(true);
            }
        });

        $employeeID = DB::table('overtime_requests')
            ->select(
                'employee_id as employeeId'
            )
            ->get();

        foreach ($employeeID as $ids) {
            DB::statement("update overtime_requests set ordered_by = '$ids->employeeId' where employee_id='$ids->employeeId';");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('overtime_requests', function (Blueprint $table) {
            if (Schema::hasColumn('overtime_requests', 'ordered_by')) {
                $table->dropColumn('ordered_by');
            }
        });
    }
}
