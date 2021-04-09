<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArtFamCaraDetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('art_fam_cara_dets', function (Blueprint $table) {
            $table->Increments('FamCaraDetID');
            $table->Integer('fam_cara_id')->unsigned();
            $table->string('name',50); // ken 7rouf las9in leb3athhom 
            $table->string('label',240); 
            $table->boolean('required')->default(true);
            $table->string('type',20); // number string 
            $table->string('input',20); // text select check radio
            $table->timestamps();
            $table->foreign('fam_cara_id')->references('FamCaraID')->on('art_fam_caras');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('art_fam_cara_dets');
    }
}
