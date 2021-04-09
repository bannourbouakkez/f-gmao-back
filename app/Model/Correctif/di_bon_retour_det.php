<?php

namespace App\Model\Correctif;

use Illuminate\Database\Eloquent\Model;

class di_bon_retour_det extends Model
{
    protected $guarded = ['RetourDetID','created_at','updated_at'];
    protected $primaryKey = 'RetourDetID';
}
