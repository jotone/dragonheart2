<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFractionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tbl_fraction', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->string('title');
            $table->string('slug');
            $table->string('img_url');
            $table->string('bg_img');
            $table->string('card_img');
            $table->string('type',16);
            $table->text('description');
            $table->text('short_description');
            $table->tinyInteger('position');
            $table->text('cards');
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
        Schema::table('tbl_fraction', function (Blueprint $table) {
            Schema::drop('tbl_fraction');
        });
    }
}
