<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrevInterventionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prev_interventions', function (Blueprint $table) {
            $table->Increments('InterventionID');
            $table->BigInteger('user_id')->unsigned();
            $table->Integer('equipement_id')->unsigned();
            $table->Integer('tache_id')->nullable();//->unsigned(); // ma na3rach 3léch nullable , 5thitha mel ot 
            $table->string('type',100);
            $table->date('ddr'); // date_derniere_realisation
            $table->date('ancien_ddr')->nullable();
            $table->date('date_resultat');//->nullable();
            $table->string('observation',240)->nullable();
            $table->Integer('nb_operateur')->nullable();
            $table->Integer('frequence');
            $table->Integer('h')->nullable();
            $table->Integer('m')->nullable();
            $table->string('parametrage',100);
            $table->Integer('decalage')->default(0);
            $table->Integer('ancien_decalage')->default(0);
            $table->boolean('isPlanified')->default(true);
            $table->Integer('lastOtp')->nullable();
            $table->dateTime('last_modif')->nullable();
            $table->BigInteger('modifieur_user_id')->nullable();
            $table->smallInteger('NbDeModif')->default(0);
            $table->boolean('exist')->default(true);
            $table->timestamps();
            $table->foreign('equipement_id')->references('EquipementID')->on('equi_equipements');
            $table->foreign('user_id')->references('id')->on('users');
          //$table->foreign('tache_id')->references('TacheID')->on('equi_taches');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('prev_interventions');
    }
}
