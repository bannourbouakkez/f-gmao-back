<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLignesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lignes', function (Blueprint $table) {
            $table->Increments('LigneID');
            $table->Integer('leunite_id')->unsigned();
            $table->string('ligne',240);
            $table->timestamps();
            $table->foreign('leunite_id')->references('LeUniteID')->on('les_unites');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lignes');
    }
}
