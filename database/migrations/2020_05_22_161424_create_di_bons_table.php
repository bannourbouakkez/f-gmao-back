<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiBonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('di_bons', function (Blueprint $table) {
            $table->Increments('BonID');
            $table->Integer('ot_id')->unsigned();
            $table->bigInteger('user_id')->unsigned();
            $table->string('rapport',240)->nullable();
            $table->dateTime('last_modif')->nullable();
            $table->BigInteger('modifieur_user_id')->nullable();
            $table->smallInteger('NbDeModif')->default(0);
            $table->boolean('exist')->default(true); 
            $table->timestamps();
            $table->foreign('ot_id')->references('OtID')->on('di_ots');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('di_bons');
    }
}
