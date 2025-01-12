<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConversationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('initializer_id');
            $table->unsignedBigInteger('dependent_id');

            $table->foreign('initializer_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('dependent_id')->references('id')->on('users')->onDelete('cascade');

            $table->tinyInteger('is_deleted_for_initializer')->default(0);
            $table->tinyInteger('is_deleted_for_dependent')->default(0);
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
        Schema::dropIfExists('conversations');
    }
}
