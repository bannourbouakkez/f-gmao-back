<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEquiAnomaliesEquipementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('equi_anomalies_equipements', function (Blueprint $table) {
            $table->Increments('AnomalieEquipementID');
            $table->Integer('anomalie_id')->unsigned();
            $table->Integer('equipement_id')->unsigned();
            $table->timestamps();
            $table->foreign('anomalie_id')->references('AnomalieID')->on('equi_anomalies');
            $table->foreign('equipement_id')->references('EquipementID')->on('equi_equipements');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('equi_anomalies_equipements');
    }
}
