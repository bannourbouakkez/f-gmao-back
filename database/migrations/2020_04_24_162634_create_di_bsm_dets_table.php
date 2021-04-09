<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiBsmDetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('di_bsm_dets', function (Blueprint $table) {
            $table->Increments('BsmDetID');
            $table->Integer('bsm_id')->unsigned();
            $table->Integer('article_id')->unsigned();
            $table->float('qte',10,4)->default(0);
            $table->string('motif', 240)->nullable();
            $table->boolean('exist')->default(true); 
            $table->timestamps();
            $table->foreign('bsm_id')->references('BsmID')->on('di_bsms');
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
        Schema::dropIfExists('di_bsm_dets');
    }
}
