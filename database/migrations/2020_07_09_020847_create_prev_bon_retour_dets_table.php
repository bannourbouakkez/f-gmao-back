<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrevBonRetourDetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prev_bon_retour_dets', function (Blueprint $table) {
            $table->Increments('RetourDetID');
            $table->Integer('retour_id')->unsigned(); 
            $table->Integer('article_id')->unsigned(); 
            $table->float('qtear',10,4)->default(0);
            $table->float('qter',10,4)->nullable();
            $table->timestamps();
            $table->foreign('retour_id')->references('RetourID')->on('prev_bon_retours');
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
        Schema::dropIfExists('prev_bon_retour_dets');
    }
}
