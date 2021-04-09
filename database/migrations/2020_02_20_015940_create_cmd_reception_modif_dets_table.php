<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCmdReceptionModifDetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cmd_reception_modif_dets', function (Blueprint $table) {
            $table->bigIncrements('CmdRecModifDetID');
            $table->bigInteger('cmd_rec_modif_id')->unsigned();
            $table->bigInteger('daarticle_id')->unsigned();
            $table->float('AnQteRecu',10,4)->default(0);
            $table->float('NvQteRecu',10,4)->default(0);
            $table->timestamps();
            $table->foreign('cmd_rec_modif_id')->references('CmdRecModifID')->on('cmd_reception_modifs');
            $table->foreign('daarticle_id')->references('id')->on('daarticles');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cmd_reception_modif_dets');
    }
}
