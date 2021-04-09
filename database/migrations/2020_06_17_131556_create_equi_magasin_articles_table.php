<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEquiMagasinArticlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('equi_magasin_articles', function (Blueprint $table) {
            $table->Increments('EquipementArticleID');
            $table->Integer('equipement_id')->unsigned();
            $table->Integer('article_id')->unsigned();
            //$table->string('des',50);
            $table->timestamps();
            $table->foreign('equipement_id')->references('EquipementID')->on('equi_equipements');
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
        Schema::dropIfExists('equi_magasin_articles');
    }
}
