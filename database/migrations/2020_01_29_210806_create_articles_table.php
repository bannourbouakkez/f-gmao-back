<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArticlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->Increments('ArticleID');
            $table->Integer('fournisseur_id')->unsigned();
            $table->Integer('unite_id')->unsigned();
            $table->Integer('famille_id')->unsigned();
            $table->Integer('sous_famille_id')->unsigned()->default(0);
            
            $table->Integer('emplacement_id')->unsigned()->default(0);

            $table->Integer('imgs_dossier_id')->unsigned()->default(1); //----------
            $table->Integer('fichiers_dossier_id')->unsigned()->default(1); //----------

            $table->string('des',240);
            $table->string('code_article',240)->nullable();
            $table->string('code_a_barre',240)->nullable();
            $table->float('PrixHT',10,4)->default(0);

            $table->float('artTVA',10,4)->default(0); //----------
            $table->float('stock',10,4)->default(0);
            $table->float('stock_min',10,4)->nullable()->default(0);
            $table->float('stock_max',10,4)->nullable()->default(0);
            $table->float('stock_alert',10,4)->nullable()->default(0);
            $table->string('remarques',240)->nullable();


            //$table->float('PrixTT',10,4)->default(0); // wallah ma ni 3aref chnowa
            $table->boolean('exist')->default(true);
            $table->timestamps();//->useCurrent();
            $table->foreign('fournisseur_id')->references('id')->on('fournisseurs');
            $table->foreign('unite_id')->references('UniteID')->on('unites');
            $table->foreign('famille_id')->references('FamilleID')->on('art_familles');
            $table->foreign('sous_famille_id')->references('FamCaraID')->on('art_fam_caras');

            
            $table->foreign('emplacement_id')->references('EmplacementID')->on('art_emplacements');
            $table->foreign('imgs_dossier_id')->references('DossierID')->on('dossiers');
            $table->foreign('fichiers_dossier_id')->references('FileID')->on('dossiers');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('articles');
    }
}
