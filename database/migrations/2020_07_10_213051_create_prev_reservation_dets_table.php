<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrevReservationDetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('prev_reservation_dets', function (Blueprint $table) {
            $table->Increments('ReservationDetID');
            $table->Integer('reservation_id')->unsigned();
            $table->Integer('article_id')->unsigned();
            $table->float('qte',10,4)->default(0);
            $table->string('motif', 240)->nullable();
            $table->boolean('exist')->default(true); 
            $table->timestamps();
            $table->foreign('reservation_id')->references('ReservationID')->on('prev_reservations');
            $table->foreign('article_id')->references('ArticleID')->on('articles');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('prev_reservation_dets');
    }
}
