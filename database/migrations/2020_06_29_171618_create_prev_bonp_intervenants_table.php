<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrevBonpIntervenantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prev_bonp_intervenants', function (Blueprint $table) {
            $table->Increments('BonpIntervenantID');
            $table->Integer('bonp_id')->unsigned();
            $table->Integer('intervenant_id')->unsigned();
            $table->Integer('tache_id')->nullable();//->unsigned();
            
            $table->date('date1');
            $table->time('time1',0);
            $table->dateTime('datetime1');

            $table->date('date2');
            $table->time('time2',0);
            $table->dateTime('datetime2');
            
            $table->string('description',240)->nullable();
            $table->float('note',10,4)->default(0);
            
            $table->boolean('exist')->default(true); 
            $table->timestamps();
            $table->foreign('bonp_id')->references('BonpID')->on('prev_bonps');
            $table->foreign('intervenant_id')->references('IntervenantID')->on('intervenants');
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
        Schema::dropIfExists('prev_bonp_intervenants');
    }
}
