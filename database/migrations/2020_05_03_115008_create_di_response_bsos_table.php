<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiResponseBsosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('di_response_bsos', function (Blueprint $table) {
            $table->Increments('ResponseBsoID');
            $table->Integer('bso_id')->unsigned();
            $table->bigInteger('user_id')->unsigned();
            $table->string('message', 240)->nullable();
            $table->boolean('exist')->default(true); 
            $table->timestamps();
            $table->foreign('bso_id')->references('BsoID')->on('di_bsos');
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
        Schema::dropIfExists('di_response_bsos');
    }
}
