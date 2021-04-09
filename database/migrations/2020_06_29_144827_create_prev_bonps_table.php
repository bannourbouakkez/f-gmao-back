<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrevBonpsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prev_bonps', function (Blueprint $table) {
            $table->Increments('BonpID');
            $table->Integer('otp_id')->unsigned();
            $table->bigInteger('user_id')->unsigned();
            $table->dateTime('date_cloture');
            $table->string('rapport',240)->nullable();
            $table->dateTime('last_modif')->nullable();
            $table->BigInteger('modifieur_user_id')->nullable();
            $table->smallInteger('NbDeModif')->default(0);
            $table->boolean('exist')->default(true); 
            $table->timestamps();
            $table->foreign('otp_id')->references('OtpID')->on('prev_otps');
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
        Schema::dropIfExists('prev_bonps');
    }
}
