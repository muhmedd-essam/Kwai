<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoomChairsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('room_chairs', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('index')->comment('ex: 1, 2, ...(9 or 12)');
            $table->tinyInteger('is_locked')->default(1)->comment('0 => locked, 1 => opened');
            $table->tinyInteger('is_muted')->default(0)->comment('0 => no, 1 => yes');
            $table->tinyInteger('is_muted_by_user')->default(0)->comment('0 => no, 1 => yes');
            $table->tinyInteger('is_carizma_counter')->default(0)->comment('0 => no, 1 => yes');
            $table->bigInteger('carizma_counter')->default(0);
            $table->timestamp('carizma_opened_at')->nullable()->default(null);

            $table->foreignId('room_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onUpdate('cascade')->onDelete('set null')->default(null);

            $table->datetime('user_up_at')->default(now());
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
        Schema::dropIfExists('room_chairs');
    }
}
