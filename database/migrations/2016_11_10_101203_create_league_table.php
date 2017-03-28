<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLeagueTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbl_league', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->string('title');
            $table->string('slug');
            $table->integer('min_lvl')->unsigned();
            $table->integer('max_lvl')->unsigned();
            $table->integer('gold_per_win')->unsigned();
            $table->integer('gold_per_loose');
            $table->integer('silver_per_win')->unsigned();
            $table->integer('silver_per_loose');
            $table->integer('rating_per_win')->unsigned();
            $table->integer('rating_per_loose');
            $table->integer('prem_gold_per_win')->unsigned();
            $table->integer('prem_gold_per_loose');
            $table->integer('prem_silver_per_win')->unsigned();
            $table->integer('prem_silver_per_loose');
            $table->integer('min_amount')->unsigned();
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
        Schema::table('tbl_league', function (Blueprint $table) {
            Schema::drop('tbl_league');
        });
    }
}
