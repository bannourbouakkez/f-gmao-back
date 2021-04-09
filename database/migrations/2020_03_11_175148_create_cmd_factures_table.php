<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCmdFacturesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cmd_factures', function (Blueprint $table) {
            $table->Increments('FactureID');
            $table->Integer('fournisseur_id')->unsigned();
            $table->float('TTTTC',10,4)->default(0);       
            $table->boolean('exist')->default(true);                                 
            $table->timestamps();
            $table->foreign('fournisseur_id')->references('id')->on('fournisseurs');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cmd_factures');
    }
}
