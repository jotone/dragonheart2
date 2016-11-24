<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBattleMembersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbl_battle_members', function (Blueprint $table) {
            $table->increments('id');
            $table->string('user_id');
            $table->integer('battle_id')->unsigned();
            $table->string('user_deck_race');
            $table->tinyInteger('available_to_change')->unsigned();
            $table->text('user_deck');
            $table->text('user_hand');
            $table->text('user_discard');
            $table->text('magic_effects');
            $table->integer('user_energy')->unsigned();
            $table->tinyInteger('user_ready')->unsigned();
            $table->string('player_source',16);
            $table->text('card_source');
            $table->text('card_to_play');
            $table->text('addition_data');
            $table->tinyInteger('round_passed')->unsigned();
            $table->string('turn_expire');
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
        Schema::table('tbl_battle_members', function (Blueprint $table) {
            Schema::drop('tbl_battle_members');
        });
    }
}
