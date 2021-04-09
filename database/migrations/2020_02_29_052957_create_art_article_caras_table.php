<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArtArticleCarasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('art_article_caras', function (Blueprint $table) {
            $table->Increments('ArtCaraID');
            $table->Integer('article_id')->unsigned();
            $table->Integer('fam_cara_det_id')->unsigned();
            $table->Integer('cara_id')->unsigned();
            $table->string('text',240)->nullable();
            $table->timestamps();
            $table->foreign('article_id')->references('ArticleID')->on('articles');
            $table->foreign('fam_cara_det_id')->references('FamCaraDetID')->on('art_fam_cara_dets');
            $table->foreign('cara_id')->references('CaraID')->on('art_caras');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('art_article_caras');
    }
}
