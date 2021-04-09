<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiDiPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('di_di_plans', function (Blueprint $table) {
            $table->Increments('PlanID');
            $table->BigInteger('user_id')->unsigned();
            $table->Integer('di_id')->unsigned();
            $table->date('date');
            $table->time('time',0);
            $table->dateTime('datetime');
            $table->string('type', 100);
            $table->dateTime('date_execution')->nullable();
            $table->dateTime('last_modif')->nullable();
            $table->BigInteger('modifieur_user_id')->nullable();
            $table->smallInteger('NbDeModif')->default(0);
            $table->smallInteger('isExecuted')->default(0);
            $table->boolean('exist')->default(true); 
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('di_id')->references('DiID')->on('di_dis'); 
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('di_di_plans');
    }
}
