<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoomContributionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('room_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            
            $table->unsignedBigInteger('receiver_id');
            $table->unsignedBigInteger('sender_id');

            $table->foreign('receiver_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('sender_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');

            $table->foreignId('gift_id')->nullable()->constrained()->onUpdate('cascade')->onDelete('set null');
            $table->integer('quantity');
            $table->double('amount'); //Total amount of contribution
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
        Schema::dropIfExists('room_contributions');
    }
}
