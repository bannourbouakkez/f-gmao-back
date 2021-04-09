<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEquipementSousEquipementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('equipement_sous_equipements', function (Blueprint $table) {
            $table->Increments('EquipementSousEquipementID');
            $table->Integer('equipement_id')->unsigned();
            $table->Integer('sous_equipement_id')->unsigned();
            $table->timestamps();
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
        Schema::dropIfExists('equipement_sous_equipements');
    }
}
