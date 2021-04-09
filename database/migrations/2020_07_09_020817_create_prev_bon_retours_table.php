<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrevBonRetoursTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prev_bon_retours', function (Blueprint $table) {
            $table->Increments('RetourID');
            $table->Integer('bonp_id')->nullable();//->unsigned();

            $table->Integer('otp_id')->nullable(); // 9bal el git 
            $table->bigInteger('user_id')->unsigned(); // 9bal el git 

            $table->string('statut',50)->default('enAttente');
            $table->dateTime('last_modif')->nullable();
            $table->BigInteger('modifieur_user_id')->nullable();
            $table->smallInteger('NbDeModif')->default(0);
            $table->timestamps(); 

            $table->foreign('user_id')->references('id')->on('users'); // 9bal el git 

            //$table->foreign('bonp_id')->references('BonpID')->on('prev_bonps');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //Schema::drop('prev_bon_retours');
        Schema::dropIfExists('prev_bon_retours');
    }
}
