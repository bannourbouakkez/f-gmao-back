<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCmdBonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cmd_bons', function (Blueprint $table) {
            $table->Increments('BonID');
            $table->bigInteger('cmd_rec_id')->unique()->unsigned();
            $table->bigInteger('user_id')->unsigned();
            $table->boolean('isModified')->default(false);
            $table->boolean('isFactured')->default(false);
           // $table->boolean('exist')->default(true);                                 
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
        Schema::dropIfExists('cmd_bons');
    }
}
