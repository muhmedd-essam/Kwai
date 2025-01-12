<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChargeAgentAdminHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('charge_agent_admin_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('charge_agent_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            
            $table->double('amount');
            $table->tinyInteger('type')->comment('0 => deposite, 1 => withdrawal');
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
        Schema::dropIfExists('charge_agent_admin_histories');
    }
}
