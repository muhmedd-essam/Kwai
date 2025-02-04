<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReelCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reel_comments', function (Blueprint $table) {
            $table->id();
            
            $table->string('body');

            $table->foreignId('reel_id')->constrained()->onUpdate('cascade')->onDelete('cascade');

            $table->foreignId('user_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            
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
        Schema::dropIfExists('reel_comments');
    }
}
