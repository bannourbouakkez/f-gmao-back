<?php

namespace App\Model\Correctif;

use Illuminate\Database\Eloquent\Model;

class di_bon extends Model
{
    protected $guarded = ['BonID','created_at','updated_at'];
    protected $primaryKey = 'BonID';
}
