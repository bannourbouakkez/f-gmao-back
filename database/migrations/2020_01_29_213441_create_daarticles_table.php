<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDaarticlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('daarticles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('da_id')->unsigned();
            $table->Integer('article_id')->unsigned();
            $table->Integer('qte');
            $table->string('motif',240)->nullable();
            // pour la perfermonce // $table->boolean('existdanscmddaarticles')->default(true); // pour eviter la longe recherche dans add commande 
            $table->timestamps();//->useCurrent();
            $table->foreign('da_id')->references('id')->on('das');
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
        Schema::dropIfExists('daarticles');
    }
}
