<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrevBonRetourHistsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prev_bon_retour_hists', function (Blueprint $table) {
            $table->Increments('RetourHistID');
            $table->Integer('retour_id')->unsigned(); 
            $table->Integer('article_id')->unsigned();
            $table->bigInteger('user_id')->unsigned();
            $table->string('type',240);//[pu,p]
            $table->float('value',10,4)->default(0);
            $table->boolean('isModification')->default(true); 
            $table->timestamps();
            $table->foreign('retour_id')->references('RetourID')->on('prev_bon_retours');
            $table->foreign('article_id')->references('ArticleID')->on('articles');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('prev_bon_retour_hists');
    }
}
