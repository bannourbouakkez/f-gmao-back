<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSousEquipementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sous_equipements', function (Blueprint $table) {
            $table->Increments('SousEquipementID');
            // $table->Integer('equipement_id')->unsigned();
            $table->string('sous_equipement',240);
            $table->boolean('exist')->default(true);    
            $table->timestamps();
            //$table->foreign('equipement_id')->references('EquipementID')->on('equipements');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sous_equipements');
    }
}
