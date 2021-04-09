<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCmdFactureDetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cmd_facture_dets', function (Blueprint $table) {
            $table->Increments('FacDetID');
            $table->Integer('facture_id')->unsigned();
            $table->Integer('bon_id')->unsigned();
            $table->timestamps();
            $table->foreign('facture_id')->references('FactureID')->on('cmd_factures');
            $table->foreign('bon_id')->references('BonID')->on('cmd_bons');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cmd_facture_dets');
    }
}
