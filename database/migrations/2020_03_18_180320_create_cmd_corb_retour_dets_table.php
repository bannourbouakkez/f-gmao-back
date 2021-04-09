<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCmdCorbRetourDetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cmd_corb_retour_dets', function (Blueprint $table) {
            $table->Increments('RetourDetID');
            $table->Integer('retour_id')->unsigned();
            $table->Integer('article_id')->unsigned();
            $table->float('QteRet',10,4)->default(0);
            $table->timestamps();
            $table->foreign('retour_id')->references('RetourID')->on('cmd_corb_retours');
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
        Schema::dropIfExists('cmd_corb_retour_dets');
    }
}
