<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCmdRartsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cmd_rarts', function (Blueprint $table) {
            $table->bigIncrements('CmdRartID');
            $table->bigInteger('user_id')->unsigned(); 
            $table->Integer('commande_id')->unsigned(); 
            $table->string('statut',50)->nullable();
            $table->boolean('vu')->default(true);
            $table->string('remarques',240)->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('commande_id')->references('CommandeID')->on('cmds');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cmd_rarts');
    }
}
