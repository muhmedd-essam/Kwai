<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVideoGiftsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('video_gifts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->double('price');
            $table->string('cover');
            $table->string('svga');
            $table->foreignId('video_gift_genere_id')->constrained()->onUpdate('cascade')->onDelete('cascade');

            $table->tinyInteger('type')->default(0)->comment('0 => normal, 1 => box, 2 => multiply');
            $table->string('related_gift_ids')->nullable()->default(null);
            $table->integer('sending_counter')->nullable()->default(0);
            $table->integer('required_sending_counter')->nullable()->default(null);
            $table->integer('surprise_gift_id')->nullable();

            
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
        Schema::dropIfExists('video_gifts');
    }
}
