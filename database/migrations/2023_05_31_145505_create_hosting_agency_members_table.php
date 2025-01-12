<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHostingAgencyMembersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hosting_agency_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hosting_agency_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('user_id')->unique()->constrained()->onUpdate('cascade')->onDelete('cascade');

            $table->unsignedBigInteger('current_target_id');
            $table->foreign('current_target_id')->on('hosting_agency_targets')->references('id')->onUpdate('cascade')->onDelete('cascade')->default(1);

            $table->unsignedBigInteger('day_salary')->default(20);
            $table->datetime('update_day_salary_at')->nullable();

            $table->unsignedBigInteger('current_target_video_id');
            $table->foreign('current_target_video_id')->on('hosting_agency_video_targets')->references('id')->onUpdate('cascade')->onDelete('cascade')->default(1);

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
        Schema::dropIfExists('hosting_agency_members');
    }
}
