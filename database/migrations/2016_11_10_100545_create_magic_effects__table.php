<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMagicEffectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbl_magic_effect', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->string('title');
            $table->string('slug');
            $table->string('img_url');
            $table->text('description');
            $table->integer('energy_cost')->unsigned();
            $table->integer('price_gold')->unsigned();
            $table->integer('price_silver')->unsigned();
            $table->integer('usage_count')->unsigned();
            $table->text('effect_actions');
            $table->integer('min_league')->unsigned();
            $table->string('fraction');
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
        Schema::table('tbl_magic_effect', function (Blueprint $table) {
            Schema::drop('tbl_magic_effect');
        });
    }
}
