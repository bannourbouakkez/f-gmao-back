<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrevReservationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prev_reservations', function (Blueprint $table) {
            $table->Increments('ReservationID');
            $table->Integer('otp_id')->unsigned();
            $table->bigInteger('user_id')->unsigned();
            $table->string('statut',50)->default('ouvert');
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
        Schema::dropIfExists('prev_reservations');
    }
}
