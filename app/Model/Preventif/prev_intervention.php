<?php

namespace App\Model\Preventif;

use Illuminate\Database\Eloquent\Model;

class prev_intervention extends Model
{
    protected $guarded = ['InterventionID','created_at','updated_at'];
    protected $primaryKey = 'InterventionID';
}
