<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArt2UsesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('art_2_uses', function (Blueprint $table) {
            $table->Increments('UseID');
            $table->BigInteger('user_id')->unsigned();
            $table->Integer('intervenant_id')->unsigned();
            $table->Integer('equipement_id')->unsigned()->nullable();
            $table->Integer('bso_id')->unsigned();
           
            $table->date('date');
            $table->time('time',0);
            $table->dateTime('datetime');

            $table->boolean('isOpened')->default(true);
            $table->dateTime('date_cloture')->nullable();
            $table->smallInteger('NbDeModif')->default(0);
            $table->boolean('exist')->default(true); 

            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('intervenant_id')->references('IntervenantID')->on('intervenants');
            $table->foreign('equipement_id')->references('EquipementID')->on('equi_equipements');
            $table->foreign('bso_id')->references('BsoID')->on('di_bsos');
        
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('art_2_uses');
    }
}
