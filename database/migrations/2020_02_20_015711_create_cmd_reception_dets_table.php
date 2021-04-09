<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCmdReceptionDetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cmd_reception_dets', function (Blueprint $table) {
            $table->bigIncrements('CmdRecDetID');
            $table->bigInteger('cmd_rec_id')->unsigned();
            $table->bigInteger('daarticle_id')->unsigned();
            $table->float('QteRecu',10,4)->default(0);
            $table->timestamps();
            $table->foreign('cmd_rec_id')->references('CmdRecID')->on('cmd_receptions');
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
        Schema::dropIfExists('cmd_reception_dets');
    }
}
