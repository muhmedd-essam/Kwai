<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDecorationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('decorations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->comment('in: frame,entry,chat_bubble,room_background,room_frame');
            $table->string('cover');
            $table->string('svga')->nullable();
            $table->boolean('is_free')->comment('0 => false, 1 => true');
            $table->double('price');
            $table->string('currency_type');
            $table->integer('valid_days');
            $table->tinyInteger('is_purchased')->default(0)->comment('0 => no, 1 => yes');
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
        Schema::dropIfExists('decorations');
    }
}
