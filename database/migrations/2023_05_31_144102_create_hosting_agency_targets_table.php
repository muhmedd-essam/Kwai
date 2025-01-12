<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHostingAgencyTargetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hosting_agency_targets', function (Blueprint $table) {
            $table->id();
            $table->integer('target_no')->unique();
            $table->bigInteger('diamonds_required');
            $table->integer('hours_required');

            $table->double('salary');
            $table->double('owner_salary');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hosting_agency_targets');
    }
}
