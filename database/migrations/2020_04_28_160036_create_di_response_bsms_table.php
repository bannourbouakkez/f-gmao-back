<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiResponseBsmsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('di_response_bsms', function (Blueprint $table) {
            $table->Increments('ResponseBsmID');
            $table->Integer('bsm_id')->unsigned();
            $table->bigInteger('user_id')->unsigned();
            $table->string('reason', 240)->nullable();
            $table->boolean('exist')->default(true);
            $table->timestamps();
            $table->foreign('bsm_id')->references('BsmID')->on('di_bsms');
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
        Schema::dropIfExists('di_response_bsms');
    }
}
