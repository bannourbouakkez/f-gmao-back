<?php

namespace App\Model\Correctif;

use Illuminate\Database\Eloquent\Model;

class di_bon_retour_hist extends Model
{
    protected $guarded = ['RetourHistID','created_at','updated_at'];
    protected $primaryKey = 'RetourHistID';
}
