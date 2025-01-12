<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHostingAgenciesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hosting_agencies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description');
            $table->string('cover');
            $table->integer('aid')->unique();

            $table->unsignedBigInteger('owner_id')->unique();
            $table->foreign('owner_id')->on('users')->references('id')->onUpdate('cascade')->onDelete('cascade');

            $table->unsignedBigInteger('bd');
            $table->foreign('bd')->on('users')->references('id')->onUpdate('cascade')->onDelete('cascade');

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
        Schema::dropIfExists('hosting_agencies');
    }
}
