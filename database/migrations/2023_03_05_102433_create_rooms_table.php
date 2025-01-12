<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoomsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->integer('rid')->unique();

            $table->integer('level')->default(1);

            $table->tinyInteger('chairs_no')->comment('0 => 9, 1 => 12');
            $table->tinyInteger('type')->comment('0 => Normal, 1 => PK')->default(0);
            $table->string('name');
            $table->string('description');
            $table->string('cover');
            $table->bigInteger('contributions_value')->default(0);
            $table->datetime('update_contributions_value_at')->nullable();

            $table->unsignedBigInteger('background_id')->nullable();
            $table->foreign('background_id')->references('id')->on('room_backgrounds')->onDelete('set null')->onUpdate('cascade');

            $table->unsignedBigInteger('owner_id');
            $table->foreign('owner_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->tinyInteger('owner_in_room')->default(1)->comment('1 => yes, 0 => no');

            $table->string('password')->nullable()->default(null);
            $table->tinyInteger('has_password')->comment('0 => No, 1 => Yes')->default(0);

            $table->string('agora_channel_name')->unique();
            $table->string('pusher_channel_name')->unique();
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
        Schema::dropIfExists('rooms');
    }
}
