<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArtPpsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('art_pps', function (Blueprint $table) {
            $table->Increments('PumpID');
            $table->Integer('article_id')->unsigned();
            $table->string('TypeID',50);
            $table->Integer('ID');//->nullable();
            $table->string('type',50);
            $table->float('NvQte',10,4)->default(0);
            $table->float('NvPrixHT',10,4)->default(0);
            //$table->float('AnStock',10,4)->default(0);
            //$table->float('NvStock',10,4)->default(0);
            $table->float('AnPUMP',10,4)->default(0);
            $table->float('NvPUMP',10,4)->default(0);
            $table->boolean('exist')->default(true);
            //$table->foreign('bon_id')->references('BonID')->on('cmd_bons');
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
        Schema::dropIfExists('art_pps');
    }
}
