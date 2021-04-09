<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDateTimeTestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('date_time_tests', function (Blueprint $table) {
            $table->Increments('DateTimeID');
            $table->date('date')->nullable();
            $table->Integer('heure')->nullable()->default(0);
            $table->Integer('minute')->nullable()->default(0);
            $table->time('time', 0)->nullable();
           // $table->timestamps('time');
            $table->dateTime('datetime1')->nullable();
            //$table->timestamps('datetime2');
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
        Schema::dropIfExists('date_time_tests');
    }
}
