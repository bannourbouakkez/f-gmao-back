<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiResponseBsoDetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    { 
        Schema::create('di_response_bso_dets', function (Blueprint $table) {
            $table->bigIncrements('ResponseBsoDetID');
            $table->Integer('response_bso_id')->unsigned();
            $table->Integer('bso_det_id')->unsigned(); // 777
            $table->boolean('reponse')->default(false);
            $table->boolean('exist')->default(true); 
            $table->timestamps();
            $table->foreign('response_bso_id')->references('ResponseBsoID')->on('di_response_bsos');
            $table->foreign('bso_det_id')->references('BsoDetID')->on('di_bso_dets'); // 777
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('di_response_bso_dets');
    }
}
