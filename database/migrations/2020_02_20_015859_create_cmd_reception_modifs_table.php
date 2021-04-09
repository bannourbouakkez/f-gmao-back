<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCmdReceptionModifsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cmd_reception_modifs', function (Blueprint $table) {
            $table->bigIncrements('CmdRecModifID');
            $table->bigInteger('cmd_rec_id')->unsigned();
            $table->bigInteger('user_id')->unsigned();
            $table->string('TypeDeModif',50)->nullable(); // modification // supression  ( ki tabda supression zayid nparcouri cmd_modif_dets ) 
            $table->string('rapport',250)->nullable();
            $table->timestamps();
            $table->foreign('cmd_rec_id')->references('CmdRecID')->on('cmd_receptions');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cmd_reception_modifs');
    }
}
