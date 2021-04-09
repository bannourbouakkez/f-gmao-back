<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEquiNiveausTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('equi_niveaus', function (Blueprint $table) {
            $table->Increments('NiveauID');
            $table->Integer('niveau');
            $table->string('nom',50);
            $table->boolean('isMin')->default(false);    
            $table->boolean('isMax')->default(false);
            $table->boolean('exist')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('equi_niveaus');
    }
}
