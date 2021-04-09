<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiResponseBsmDetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('di_response_bsm_dets', function (Blueprint $table) {
            $table->bigIncrements('ResponseBsmDetID');
            $table->Integer('response_bsm_id')->unsigned();
            $table->Integer('bsm_det_id')->unsigned(); // 777
            $table->float('qte',10,4)->default(0);
            $table->boolean('exist')->default(true);
            $table->timestamps();
            $table->foreign('response_bsm_id')->references('ResponseBsmID')->on('di_response_bsms');
            $table->foreign('bsm_det_id')->references('BsmDetID')->on('di_bsm_dets');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('di_response_bsm_dets');
    }
}
