<?php

namespace App\Model\Preventif;

use Illuminate\Database\Eloquent\Model;

class prev_bon_retour extends Model
{
    protected $guarded = ['RetourID','created_at','updated_at'];
    protected $primaryKey = 'RetourID';
}
