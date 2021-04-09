<?php

namespace App\Model\Preventif;

use Illuminate\Database\Eloquent\Model;

class prev_bonp extends Model
{
    protected $guarded = ['BonpID','created_at','updated_at'];
    protected $primaryKey = 'BonpID';
}
