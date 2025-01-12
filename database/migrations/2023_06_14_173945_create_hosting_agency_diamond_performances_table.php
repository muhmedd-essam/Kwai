<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHostingAgencyDiamondPerformancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hosting_agency_diamond_performances', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('agency_member_id');
            $table->foreign('agency_member_id')->on('hosting_agency_members')->references('id')->onUpdate('cascade')->onDelete('cascade');

            $table->unsignedBigInteger('supporter_id');
            $table->foreign('supporter_id')->on('users')->references('id')->onUpdate('cascade')->onDelete('cascade');
            
            $table->foreignId('hosting_agency_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            
            $table->double('amount');
            
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
        Schema::dropIfExists('hosting_agency_diamond_performances');
    }
}
