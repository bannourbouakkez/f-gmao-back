<?php

namespace App\Model\Correctif;

use Illuminate\Database\Eloquent\Model;

class di_bon_intervenant extends Model
{
    protected $guarded = ['BonIntervenantID','created_at','updated_at'];
    protected $primaryKey = 'BonIntervenantID';
}
