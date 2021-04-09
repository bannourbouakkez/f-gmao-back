<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiBonRetoursTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('di_bon_retours', function (Blueprint $table) {
            $table->Increments('RetourID');
            $table->Integer('bon_id')->nullable();

            $table->Integer('ot_id')->nullable(); // 9bal el git 
            $table->bigInteger('user_id')->unsigned(); // 9bal el git 

            $table->string('statut',50)->default('enAttente');
            $table->dateTime('last_modif')->nullable();
            $table->BigInteger('modifieur_user_id')->nullable();
            $table->smallInteger('NbDeModif')->default(0);
            $table->timestamps();

            //$table->foreign('bon_id')->references('BonID')->on('di_bons');
            $table->foreign('user_id')->references('id')->on('users'); // 9bal el git 

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('di_bon_retours');
    }
}
