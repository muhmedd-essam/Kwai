<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMicInvitesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mic_invites', function (Blueprint $table) {
            $table->id();

            $table->foreignId('room_id')->constrained()->onUpdate('cascade')->onDelete('cascade');

            $table->foreignId('user_id')->constrained()->onUpdate('cascade')->onDelete('cascade');

            $table->foreignId('room_chair_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            
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
        Schema::dropIfExists('mic_invites');
    }
}
