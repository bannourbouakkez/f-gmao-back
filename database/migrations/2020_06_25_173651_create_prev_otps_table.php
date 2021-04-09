<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrevOtpsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prev_otps', function (Blueprint $table) {
            $table->Increments('OtpID');
            $table->Integer('intervention_id')->unsigned();
            $table->BigInteger('user_id')->unsigned();
            $table->date('date_execution');
            $table->string('statut', 100)->default('enCours');
            $table->dateTime('last_modif')->nullable();
            $table->BigInteger('modifieur_user_id')->nullable();
            $table->smallInteger('NbDeModif')->default(0);
            $table->boolean('isLast')->default(true);
            $table->boolean('exist')->default(true); 
            $table->timestamps();
            $table->foreign('intervention_id')->references('InterventionID')->on('prev_interventions');
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
        Schema::dropIfExists('prev_otps');
    }
}
