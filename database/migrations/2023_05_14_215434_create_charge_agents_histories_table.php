<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChargeAgentsHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('charge_agents_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('charge_agent_id')->constrained()->onUpdate('cascade')->onDelete('cascade');

            $table->foreignId('user_id')->nullable()->constrained()->onUpdate('cascade')->onDelete('set null');

            $table->double('amount');
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
        Schema::dropIfExists('charge_agents_histories');
    }
}
