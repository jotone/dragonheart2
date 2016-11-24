<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

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
            $table->increments('id')->unsigned();
            $table->string('login')->unique();
            $table->string('email');
            $table->string('password');
            $table->string('name');
            $table->string('birth_date',16);
            $table->string('user_gender',64);
            $table->text('address');
            $table->string('img_url');
            $table->tinyInteger('is_banned')->unsigned();
            $table->string('ban_time',16);
            $table->tinyInteger('user_role')->unsigned();
            $table->tinyInteger('user_online')->unsigned();
            $table->tinyInteger('user_busy')->unsigned();

            $table->string('user_gold');
            $table->string('user_silver');
            $table->string('user_energy');

            $table->string('user_current_deck',32);
            $table->string('last_user_deck',32);
            $table->string('user_base_fraction',32);
            $table->text('user_available_deck');
            $table->text('user_cards_in_deck');
            $table->text('user_magic');
            $table->text('user_rating');

            $table->tinyInteger('premium_activated')->unsigned();
            $table->string('premium_expire_date',16);
            $table->tinyInteger('is_activated')->unsigned();
            $table->text('activation_code');
            $table->rememberToken();
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
        Schema::drop('users');
    }
}
