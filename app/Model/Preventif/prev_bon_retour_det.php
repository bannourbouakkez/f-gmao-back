<?php

namespace App\Model\Preventif;

use Illuminate\Database\Eloquent\Model;

class prev_bon_retour_det extends Model
{
    protected $guarded = ['RetourDetID','created_at','updated_at'];
    protected $primaryKey = 'RetourDetID';
}
