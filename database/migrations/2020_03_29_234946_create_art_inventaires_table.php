<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArtInventairesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('art_inventaires', function (Blueprint $table) {
            $table->Increments('InventaireID');
            $table->Integer('famille_id')->unsigned();
            $table->Integer('sous_famille_id')->unsigned();
            $table->string('ListeIntervenants',240);
            $table->string('type',50);
            $table->Integer('NbArticles')->default(0);
            $table->timestamps();
            $table->foreign('famille_id')->references('FamilleID')->on('art_familles');
            $table->foreign('sous_famille_id')->references('FamCaraID')->on('art_fam_caras');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('art_inventaires');
    }
}
