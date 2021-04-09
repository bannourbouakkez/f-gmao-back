<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiOtsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('di_ots', function (Blueprint $table) {

            $table->Increments('OtID');
            $table->bigInteger('user_id')->unsigned();
            $table->Integer('di_id')->unsigned();
            $table->Integer('tache_id')->nullable(); //->unsigned();
            $table->date('date');
            $table->time('time',0);
            $table->dateTime('datetime');
            $table->string('statut', 100)->default('enCours');
            $table->dateTime('date_execution')->nullable();
            $table->dateTime('last_modif')->nullable();
            $table->BigInteger('modifieur_user_id')->nullable();
            $table->smallInteger('NbDeModif')->default(0);
            $table->boolean('exist')->default(true);
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('di_id')->references('DiID')->on('di_dis');
            //$table->foreign('tache_id')->references('TacheID')->on('equi_taches');
            //php artisan migrate:refresh --path=/database/migrations/2020_04_21_132546_create_di_ots_table.php
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('di_ots');
    }
}
