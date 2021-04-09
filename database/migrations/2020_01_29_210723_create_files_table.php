<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create('files', function (Blueprint $table) {
            $table->Increments('FileID');
            $table->Integer('dossier_id')->unsigned();
            $table->string('type',50)->nullable(); // passport // image ... // fichier 
            $table->string('OriginaleName',100);
            $table->string('BDName',100);
            $table->string('size',50)->nullable();
            $table->string('extention',20)->nullable();
            $table->timestamps();
            $table->foreign('dossier_id')->references('DossierID')->on('dossiers');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('files');
    }
}
