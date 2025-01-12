<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGiftReceiptsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gift_receipts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->UnsignedBigInteger('gift_id')->nullable();
            $table->foreign('gift_id')->references('gift_num')->on('daily_gifts')->onUpdate('cascade')->onDelete('set null');
            $table->date('date_received'); // تاريخ استلام الهدية
            $table->integer('current_streak')->default(1); // عدد الأيام المتتالية التي تم تسجيل الدخول فيها
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
        Schema::dropIfExists('gift_receipts');
    }
}
