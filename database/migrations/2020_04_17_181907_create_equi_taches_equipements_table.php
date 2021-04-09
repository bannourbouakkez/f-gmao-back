<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEquiTachesEquipementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('equi_taches_equipements', function (Blueprint $table) {
            $table->Increments('TacheEquipementID');
            $table->Integer('tache_id')->unsigned();
            $table->Integer('equipement_id')->unsigned();
            $table->timestamps();
            $table->foreign('tache_id')->references('TacheID')->on('equi_taches');
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
        Schema::dropIfExists('equi_taches_equipements');
    }
}
