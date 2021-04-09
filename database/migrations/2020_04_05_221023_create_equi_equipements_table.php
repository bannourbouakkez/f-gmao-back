<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEquiEquipementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('equi_equipements', function (Blueprint $table) {
            $table->Increments('EquipementID');
            $table->Integer('BG');
            $table->Integer('BD');
            $table->Integer('Niv');
            $table->Integer('niveau_id')->unsigned();
            //$table->Integer('Pere');
            $table->string('equipement',50);
            $table->boolean('exist')->default(true);
            $table->timestamps();
            $table->foreign('niveau_id')->references('NiveauID')->on('equi_niveaus');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('equi_equipements');
    }
}
