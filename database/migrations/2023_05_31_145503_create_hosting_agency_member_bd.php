<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHostingAgencyMemberBd extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Schema::dropIfExists('hosting_agency_member_bd');

        // Create the table
        Schema::create('hosting_agency_member_bd', function (Blueprint $table) {
            $table->id();

            $table->UnsignedBigInteger('hosting_agency_id')->nullable();
            $table->foreign('hosting_agency_id')->references('id')->on('hosting_agencies')->onUpdate('cascade')->onDelete('set null');

            $table->UnsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('set null');

            $table->UnsignedBigInteger('owner_bd')->nullable();
            $table->foreign('owner_bd')->references('id')->on('users')->onUpdate('cascade')->onDelete('set null');

            $table->unsignedBigInteger('current_target_id');
            $table->foreign('current_target_id')->references('id')->on('hosting_agency_targets_bd')->onUpdate('cascade')->onDelete('cascade');

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
        Schema::dropIfExists('hosting_agency_member_bd');
    }
}
