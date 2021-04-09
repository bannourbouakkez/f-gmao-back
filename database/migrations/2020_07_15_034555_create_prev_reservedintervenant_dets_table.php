<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrevReservedintervenantDetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prev_reservedintervenant_dets', function (Blueprint $table) {
            $table->Increments('ReservationIntervenantID');
            $table->Integer('intreservation_id')->unsigned();
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
            $table->foreign('intervenant_id')->references('IntervenantID')->on('intervenants');
            $table->foreign('intreservation_id')->references('IntReservationID')->on('prev_reservedintervenants');
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
        Schema::dropIfExists('prev_reservedintervenant_dets');
    }
}
