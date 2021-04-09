<?php

namespace App\Model\Preventif;

use Illuminate\Database\Eloquent\Model;

class prev_bonp_intervenant extends Model
{
    protected $guarded = ['BonpIntervenantID','created_at','updated_at'];
    protected $primaryKey = 'BonpIntervenantID';
}
