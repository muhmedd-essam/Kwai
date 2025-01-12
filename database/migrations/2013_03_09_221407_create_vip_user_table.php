<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVIPuserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vip_user', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->UnsignedBigInteger('default_frame_id')->nullable();
            $table->foreign('default_frame_id')->references('id')->on('decorations')->onUpdate('cascade')->onDelete('set null');

            $table->UnsignedBigInteger('default_entry_id')->nullable();
            $table->foreign('default_entry_id')->references('id')->on('decorations')->onUpdate('cascade')->onDelete('set null');

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
        Schema::dropIfExists('_v_i_puser');
    }
}
