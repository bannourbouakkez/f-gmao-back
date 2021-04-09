<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('das', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('ref', 10)->unique();
            $table->bigInteger('user_id')->unsigned();
            $table->Integer('delai');
            $table->string('remarques', 240)->nullable();
            $table->string('statut',20)->default('encours');//encours//renvoye//enattente//repporte//confirme/rejete
            $table->boolean('exist')->default(true); 
            $table->timestamps();//->useCurrent();
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
        Schema::dropIfExists('das');
    }
}
