<?php

namespace App\Model\Preventif;

use Illuminate\Database\Eloquent\Model;

class prev_bon_retour_hist extends Model
{
    protected $guarded = ['RetourHistID','created_at','updated_at'];
    protected $primaryKey = 'RetourHistID';
}
