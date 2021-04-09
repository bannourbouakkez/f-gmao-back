<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArtInventaireDetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('art_inventaire_dets', function (Blueprint $table) {
            $table->Increments('InventaireDetID'); 
            $table->Integer('inventaire_id')->unsigned();
            $table->Integer('article_id')->unsigned();
            $table->Integer('AnStock');
            $table->Integer('NvStock');
            $table->timestamps();
            $table->foreign('inventaire_id')->references('InventaireID')->on('art_inventaires');
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
        Schema::dropIfExists('art_inventaire_dets');
    }
}
