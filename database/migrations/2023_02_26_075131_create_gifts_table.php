<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGiftsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gifts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('cover');
            $table->string('svga')->nullable()->default(null);
            $table->double('price');
            $table->unsignedBigInteger('category_gift_id');
            $table->foreign('category_gift_id')->references('id')->on('category_gifts')->onDelete('cascade');
            $table->tinyInteger('type')->default(0)->comment('0 => normal, 1 => multiply');

            $table->timestamps();

            // room_gift_type_id
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gifts');
    }
}
