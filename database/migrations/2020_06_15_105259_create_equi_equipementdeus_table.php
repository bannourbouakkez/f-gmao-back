<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEquiEquipementdeusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('equi_equipementdeus', function (Blueprint $table) {
            $table->Increments('EquipementID');
            $table->dateTime('mise_en_service')->nullable();
            $table->boolean('isMarche')->default(true); 
            $table->float('prixTTC',10,4)->nullable();
            $table->Integer('dossier_id')->unsigned()->nullable();
            $table->boolean('exist')->default(true); 
            $table->timestamps();
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
        Schema::dropIfExists('equi_equipementdeus');
    }
}
