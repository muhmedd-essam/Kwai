<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDailyGiftsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('daily_gifts', function (Blueprint $table) {
            $table->id();
            $table->UnsignedBigInteger('gift_num')->nullable();
            $table->string('gift_name');
            $table->string('gift_type'); // نوع الهدية بدون قيود enum
            $table->integer('amount')->nullable(); // الكمية للهدايا من نوع Gold أو Diamond
            $table->UnsignedBigInteger('decoration_id')->nullable();
            $table->foreign('decoration_id')->references('id')->on('decorations')->onUpdate('cascade')->onDelete('set null');

            $table->integer('day_number'); // رقم اليوم في السلسلة
            $table->string('user_type'); // نوع المستخدم: 'normal' أو 'VIP'
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
        Schema::dropIfExists('daily_gifts');
    }
}
