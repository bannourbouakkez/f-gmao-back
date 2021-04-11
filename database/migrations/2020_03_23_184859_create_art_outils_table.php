<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArtOutilsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('art_outils', function (Blueprint $table) {

            $table->Increments('OutilID');
            $table->Integer('secteur_id')->unsigned()->nullable();
            $table->Integer('type_id')->unsigned()->nullable();
            $table->Integer('etat_id')->unsigned()->nullable();
            //$table->Integer('outil_utilisations_id')->unsigned()->nullable();
            
            $table->string('code_outil',240)->nullable();
            $table->string('des',240);
            $table->string('num_serie',240)->nullable();
            $table->string('num_modele',240)->nullable();
            $table->string('fournisseur',240)->nullable();

            $table->float('prixTTC',10,4)->default(0)->nullable();

            $table->date('achat')->nullable();
            $table->date('FinGarentie')->nullable();
            $table->date('MiseEnService')->nullable();

            $table->string('remarques',240)->nullable();

            $table->Integer('imgs_dossier_id')->unsigned(); //----------
            $table->Integer('fichiers_dossier_id')->unsigned(); //----------
            $table->boolean('reserve')->default(false);
            $table->boolean('exist')->default(true);
            $table->timestamps();

            $table->foreign('secteur_id')->references('SecteurID')->on('art_secteurs');
            $table->foreign('type_id')->references('TypeID')->on('art_types');
            $table->foreign('etat_id')->references('EtatID')->on('art_etats');
            //$table->foreign('outil_utilisations_id')->references('OutilUtilisationsID')->on('art_outil_utilisations');

            $table->foreign('imgs_dossier_id')->references('DossierID')->on('dossiers');
            $table->foreign('fichiers_dossier_id')->references('DossierID')->on('dossiers');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('art_outils');
    }
}
