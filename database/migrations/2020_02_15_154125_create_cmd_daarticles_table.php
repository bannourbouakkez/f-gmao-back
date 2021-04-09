<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCmdDaarticlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cmd_daarticles', function (Blueprint $table) {
            $table->bigIncrements('CmdDaArticleID');
            $table->Integer('commande_id')->unsigned();
            $table->bigInteger('daarticle_id')->unsigned();//->unique(); // nthabbit féha la7kéya tji walla normalement mrigil
            $table->float('QteCmde',10,4)->default(0);
            $table->float('TTQteRecu',10,4)->default(0);
            $table->timestamps();
            $table->foreign('commande_id')->references('CommandeID')->on('cmds');
            $table->foreign('daarticle_id')->references('id')->on('daarticles');
            
        });
    }

    //$table->float('amount', 8, 2)
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cmd_daarticles');
    }
}
