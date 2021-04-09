<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiBsosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('di_bsos', function (Blueprint $table) {
            $table->Increments('BsoID');
            $table->Integer('ot_id')->nullable();//->unsigned(); 
            $table->boolean('isCorrectif')->nullable();
            $table->bigInteger('user_id')->unsigned();
            $table->string('statut',50)->default('ouvert');
            $table->boolean('exist')->default(true); 
            $table->timestamps();
            //$table->foreign('ot_id')->references('OtID')->on('di_ots');
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
        Schema::dropIfExists('di_bsos');
    }
}
