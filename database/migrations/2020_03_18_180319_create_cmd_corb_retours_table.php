<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCmdCorbRetoursTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cmd_corb_retours', function (Blueprint $table) {
            $table->Increments('RetourID');
            $table->bigInteger('cmd_rec_id')->unsigned();
            $table->bigInteger('user_id')->unsigned();                               
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
        Schema::dropIfExists('cmd_corb_retours');
    }
}
