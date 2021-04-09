<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArtCarasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('art_caras', function (Blueprint $table) {
            $table->Increments('CaraID');
            $table->Integer('fam_cara_det_id')->unsigned();
            $table->string('value',240); // tnajjim tkoun number donc fil controller n7awwalha el numero ken type number
            $table->boolean('default')->default(false);
            $table->timestamps();
            $table->foreign('fam_cara_det_id')->references('FamCaraDetID')->on('art_fam_cara_dets');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('art_caras');
    }
}
