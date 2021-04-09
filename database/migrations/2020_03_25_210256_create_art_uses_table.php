<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArtUsesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('art_uses', function (Blueprint $table) {
            $table->Increments('UseID');
            $table->Integer('outil_id')->unsigned();
            $table->Integer('intervenant_id')->unsigned();
            $table->Integer('leunite_id')->unsigned()->nullable();
            $table->Integer('equipement_id')->unsigned()->nullable();
            $table->Integer('sous_equipement_id')->unsigned()->nullable();

            $table->date('date')->nullable();

            $table->Integer('heure')->default(0);
            $table->Integer('minute')->default(0);

            $table->Integer('estimation')->nullable();
            $table->string('periode',50);
            $table->boolean('isOpened')->default(true);
            $table->dateTime('date_cloture')->nullable();
            $table->smallInteger('NbDeModif')->default(0);
            $table->boolean('exist')->default(true);    

            $table->timestamps();
            $table->foreign('outil_id')->references('OutilID')->on('art_outils');
            $table->foreign('intervenant_id')->references('IntervenantID')->on('intervenants');
            $table->foreign('leunite_id')->references('LeUniteID')->on('les_unites');
            $table->foreign('equipement_id')->references('EquipementID')->on('equipements');
            $table->foreign('sous_equipement_id')->references('SousEquipementID')->on('sous_equipements');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('art_uses');
    }
}
