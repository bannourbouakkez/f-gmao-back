<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFournisseurTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        
        Schema::create('fournisseurs', function (Blueprint $table) {
            $table->Increments('id');
            $table->string('nom',50);
            $table->Integer('livmode_id')->unsigned();
            $table->Integer('secteur_id')->unsigned();
            $table->Integer('dossier_id')->unsigned();
            $table->string('ref',10)->unique();
            $table->float('TVA',10,4)->default(0);
            $table->string('abr', 30)->nullable();
            $table->string('adresse', 240)->nullable();
            $table->bigInteger('tel1');
            $table->bigInteger('tel2')->nullable();
            $table->bigInteger('portable1')->nullable();
            $table->bigInteger('portable2')->nullable();
            $table->bigInteger('fax1')->nullable();
            $table->bigInteger('fax2')->nullable();
            $table->string('email1', 50)->nullable();
            $table->string('email2', 50)->nullable();
            $table->string('siteweb', 50)->nullable();
            $table->string('fraisliv', 50)->nullable();
            $table->string('autresfrais', 240)->nullable();
            $table->string('remise',50)->nullable();
            $table->string('cond_regl', 240)->nullable();
            $table->string('remarques', 240)->nullable();
            $table->timestamps();
            $table->foreign('livmode_id')->references('id')->on('livmodes');
            $table->foreign('secteur_id')->references('id')->on('secteurs');
            $table->foreign('dossier_id')->references('DossierID')->on('dossiers');

        });
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fournisseurs');
    }

    
    public function __construct()
    {
        $this->setFillable();
    }
    public function setFillable()
    {
        $fields = Schema::getColumnListing('fournisseurs');

        $this->fillable[] = $fields;
    }
}
