<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiBonUrpsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('di_bon_urps', function (Blueprint $table) {
            $table->Increments('BonUrpID');
            $table->Integer('bon_id')->unsigned(); 
            $table->Integer('article_id')->unsigned(); 
            $table->float('qted',10,4)->default(0);
            $table->float('qtea',10,4)->default(0);
            $table->float('qteu',10,4)->default(0);
           // $table->float('qtear',10,4)->default(0);
           // $table->float('qter',10,4)->nullable();
            $table->float('qtep',10,4)->default(0); // 7asha zayda 
            $table->timestamps();
            $table->foreign('bon_id')->references('BonID')->on('di_bons');
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
        Schema::dropIfExists('di_bon_urps');
    }
}
