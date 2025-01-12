<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoomBackgroundsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('room_backgrounds', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('path');
            $table->tinyInteger('is_free')->default(1)->comment('0 => no, 1 => yes');
            $table->double('price')->nullable()->default(null);

            $table->UnsignedBigInteger('room_id')->nullable();
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
        Schema::dropIfExists('room_backgrounds');
    }
}
