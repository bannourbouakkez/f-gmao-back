<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCmdsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cmds', function (Blueprint $table) {
            $table->Increments('CommandeID');
            $table->bigInteger('user_id')->unsigned(); // Commandeur
            $table->Integer('fournisseur_id')->unsigned();
            $table->string('statut',50);
            $table->boolean('exist')->default(true);
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users');
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
        Schema::dropIfExists('cmds');
    }
}
