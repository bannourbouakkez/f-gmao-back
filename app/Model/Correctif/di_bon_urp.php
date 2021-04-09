<?php

namespace App\Model\Correctif;

use Illuminate\Database\Eloquent\Model;

class di_bon_urp extends Model
{
    protected $guarded = ['BonUrpID','created_at','updated_at'];
    protected $primaryKey = 'BonUrpID';
}
