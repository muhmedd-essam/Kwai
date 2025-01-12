<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGlobalBoxesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('global_boxes', function (Blueprint $table) {
            $table->id();
            $table->double('in_box')->default(0);
            $table->double('out_box')->default(0);
            $table->double('amount')->default(0);
            $table->bigInteger('times_played')->default(0);
            $table->timestamp('last_500')->nullable()->default(null);
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
        Schema::dropIfExists('global_boxes');
    }
}
