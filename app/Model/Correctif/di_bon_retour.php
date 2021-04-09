<?php

namespace App\Model\Correctif;

use Illuminate\Database\Eloquent\Model;

class di_bon_retour extends Model
{
    protected $guarded = ['RetourID','created_at','updated_at'];
    protected $primaryKey = 'RetourID';
}
