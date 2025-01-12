<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('uid')->unique();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('profile_picture')->default("https://img.favpng.com/19/22/6/user-profile-login-computer-icons-avatar-png-favpng-LTUmpPWF4mTfMrjjFxshPHxG2_t.jpg")->nullable();
            $table->string('provider_id')->nullable()->unique();
            $table->string('provider_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('device_id');
            $table->string('device_token')->nullable()->default(null);
            //$table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();

            //Profile Data:
            $table->date('dob')->nullable();
            $table->integer('age')->nullable()->default(null);
            $table->string('gender')->nullable();
            $table->text('about_me')->nullable()->default(null);

            $table->string('country_code');

            //rest of data
            $table->bigInteger('diamond_balance')->default(0);
            $table->bigInteger('gold_balance')->default(0);

            $table->UnsignedBigInteger('default_frame_id')->nullable();
            $table->foreign('default_frame_id')->references('id')->on('decorations')->onUpdate('cascade')->onDelete('set null');

            $table->UnsignedBigInteger('default_entry_id')->nullable();
            $table->foreign('default_entry_id')->references('id')->on('decorations')->onUpdate('cascade')->onDelete('set null');

            $table->date('deactivated_until')->nullable()->default(null);

            $table->foreignId('level_id')->constrained();

            $table->UnsignedBigInteger('next_level_id');
            $table->foreign('next_level_id')->references('id')->on('levels');
            $table->integer('level_gift')->default(0)->comment('0 => Not taken, 1 => first gift, 2 => third gift');

            $table->double('exp_points')->default(0);
            $table->integer('supported_send')->default(0);
            $table->integer('supported_receive')->default(0);
            $table->tinyInteger('role')->default(0);

            $table->string('language')->default('ar');
            $table->tinyInteger('is_video_hosting')->default(0)->comment('0 => no, 1 => yes');
            $table->tinyInteger('is_video_cohosting')->default(0)->comment('0 => no, 1 => yes');

            $table->tinyInteger('is_hosting_agency_owner')->default(0);
            $table->tinyInteger('is_hosting_agent')->default(0);
            $table->tinyInteger('is_charge_agent')->default(0);
            $table->tinyInteger('is_group_owner')->default(0);

            $table->double('money')->default(0);

            $table->UnsignedBigInteger('vip')->nullable();
            $table->foreign('vip')->references('id')->on('vip_user')->onUpdate('cascade')->onDelete('set null');
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
        Schema::dropIfExists('users');
    }
}
