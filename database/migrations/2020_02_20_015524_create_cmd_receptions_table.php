<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCmdReceptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cmd_receptions', function (Blueprint $table) {
            $table->bigIncrements('CmdRecID');
            $table->Integer('commande_id')->unsigned();
            $table->bigInteger('user_id')->unsigned();
            $table->boolean('isModified')->default(true);  // modifie / propre ( pour filtre ) 
            $table->boolean('exist')->default(true); // / wa9talli ya3mel supression lel reception [si possible ]
            $table->smallInteger('NbDeModif')->default(0);
            $table->string('rapport',250)->nullable();
            $table->timestamps();
            $table->foreign('commande_id')->references('CommandeID')->on('cmds');
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
        Schema::dropIfExists('cmd_receptions');
    }
}
