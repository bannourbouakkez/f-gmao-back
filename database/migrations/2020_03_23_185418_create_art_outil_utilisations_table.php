<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArtOutilUtilisationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('art_outil_utilisations', function (Blueprint $table) {
            $table->Increments('OutilUtilisationsID');
            $table->Integer('outil_id')->unsigned();
            $table->Integer('utilisation_id')->unsigned();
            $table->timestamps();
            $table->foreign('outil_id')->references('OutilID')->on('art_outils');
            $table->foreign('utilisation_id')->references('UtilisationID')->on('art_utilisations');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('art_outil_utilisations');
    }
}
