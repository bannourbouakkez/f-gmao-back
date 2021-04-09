<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArticlesPerdusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('articles_perdus', function (Blueprint $table) {
            $table->Increments('ArticlePerduID');
            $table->Integer('article_id')->unsigned(); 
            //$table->Integer('intervenant_id')->unsigned()->nullable(); 
            $table->float('qtep',10,4)->default(0);
            //$table->string('description',240)->nullable();
            $table->timestamps();
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
        Schema::dropIfExists('articles_perdus');
    }
}
