<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiOtIntervenantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    
    public function up()
    {
        Schema::create('di_ot_intervenants', function (Blueprint $table) {
            $table->Increments('OtIntervenantID');
            $table->Integer('ot_id')->unsigned();
            $table->Integer('intervenant_id')->unsigned();
            $table->boolean('exist')->default(true);
            $table->timestamps();
            $table->foreign('ot_id')->references('OtID')->on('di_ots');
            $table->foreign('intervenant_id')->references('IntervenantID')->on('intervenants');
        });
    }
    

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('di_ot_intervenants');
    }
}
