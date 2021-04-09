<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiBsoDetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('di_bso_dets', function (Blueprint $table) {
            $table->Increments('BsoDetID');
            $table->Integer('bso_id')->unsigned();
            $table->Integer('outil_id')->unsigned();
            $table->float('estimation',10,1)->default(0);
            $table->string('periode',50)->nullable();
            $table->string('statut',50)->default('enAttente'); // refuse // enUtilisation // termine 
            $table->dateTime('date_utilisation')->nullable();
            $table->dateTime('date_termination')->nullable();
            $table->boolean('exist')->default(true); 
            $table->timestamps();
            $table->foreign('bso_id')->references('BsoID')->on('di_bsos');
            $table->foreign('outil_id')->references('OutilID')->on('art_outils');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('di_bso_dets');
    }
}
