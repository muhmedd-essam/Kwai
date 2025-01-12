<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHostingAgencyTargetsBd extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Schema::dropIfExists('hosting_agency_targets_bd');

        Schema::create('hosting_agency_targets_bd', function (Blueprint $table) {
            $table->id();
            $table->integer('target_no')->unique();
            $table->bigInteger('salary_required');
            $table->double('bd_salary');
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
        Schema::dropIfExists('hosting_agency_targets_bd');
    }
}
