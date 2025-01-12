<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSpecialUIDSTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('special_u_i_d_s', function (Blueprint $table) {
            $table->id();
            $table->string('body')->unique();
            $table->double('price');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null')->onUpdate('cascade');
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
        Schema::dropIfExists('special_u_i_d_s');
    }
}
