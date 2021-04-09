<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiDisTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('di_dis', function (Blueprint $table) {
            $table->Increments('DiID');
            $table->BigInteger('user_id')->unsigned();
            $table->BigInteger('demandeur_user_id')->unsigned();
            $table->BigInteger('recepteur_user_id')->unsigned();
            $table->Integer('equipement_id')->unsigned();
            $table->Integer('anomalie_id')->unsigned();
            $table->Integer('dossier_id')->unsigned()->nullable();
            $table->date('date');
            $table->time('time',0);
            $table->dateTime('datetime');
            $table->string('description', 240)->nullable();
            $table->string('type', 100);
            $table->string('degre', 100);
            $table->string('statut', 100)->default('enAttente');
            $table->boolean('exist')->default(true);
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('demandeur_user_id')->references('id')->on('users');
            $table->foreign('recepteur_user_id')->references('id')->on('users');
            $table->foreign('equipement_id')->references('EquipementID')->on('equi_equipements');
            $table->foreign('anomalie_id')->references('AnomalieID')->on('equi_anomalies');
            $table->foreign('dossier_id')->references('DossierID')->on('dossiers');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('di_dis');
    }
}
