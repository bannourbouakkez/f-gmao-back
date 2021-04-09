<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRartsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rarts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('da_id')->unsigned();
            $table->string('statut',20)->nullable();
            $table->smallInteger('vu')->default(0);
            $table->string('message', 240)->nullable();
            $table->smallInteger('delaidereportation')->nullable();
            $table->timestamps();//->useCurrent();// mchich tmodifi el created_at ?!! becareful 
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('da_id')->references('id')->on('das');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rarts');
    }
}
