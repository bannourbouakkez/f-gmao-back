<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArtFamCarasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('art_fam_caras', function (Blueprint $table) {
            $table->Increments('FamCaraID');
            $table->string('name_famille',50)->unique();
            $table->Integer('famille_id')->unsigned();
            $table->boolean('hasCara')->default(false);
            $table->timestamps();
            $table->foreign('famille_id')->references('FamilleID')->on('art_familles');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('art_fam_caras');
    }
}
