<?php

namespace App\Model\Correctif;

use Illuminate\Database\Eloquent\Model;

class di_di_plan extends Model
{
    protected $guarded = ['PlanID','created_at','updated_at'];
    protected $primaryKey = 'PlanID';
}
