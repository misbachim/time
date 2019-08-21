<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TimeCreateTableEventEligibilitie extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('event_eligibilities', function (Blueprint $table) {
          $table->integer('tenant_id');
          $table->integer('company_id');
          $table->integer('event_id');
          $table->string('lov_evel',10);
          $table->string('value', 20);
          $table->char('privilege', 1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('event_eligibilities');
    }
}
