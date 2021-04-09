<?php

namespace App\Model\Correctif;

use Illuminate\Database\Eloquent\Model;

class di_ot_intervenant extends Model
{
    protected $guarded = ['OtIntervenantsID','created_at','updated_at'];
    protected $primaryKey = 'OtIntervenantsID';
}
