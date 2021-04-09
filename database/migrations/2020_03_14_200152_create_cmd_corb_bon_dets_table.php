<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCmdCorbBonDetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cmd_corb_bon_dets', function (Blueprint $table) {
            $table->Increments('BonDetID');
            $table->Integer('bon_id')->unsigned();
            $table->Integer('article_id')->unsigned();
            $table->float('QteBon',10,4)->default(0);
            $table->float('PrixHT',10,4)->default(0);
            $table->float('AnPrixHT',10,4)->default(0);
           // $table->boolean('exist')->default(true);     
            $table->timestamps();
            $table->foreign('bon_id')->references('BonID')->on('cmd_corb_bons');
            $table->foreign('article_id')->references('ArticleID')->on('articles');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cmd_corb_bon_dets');
    }
}
