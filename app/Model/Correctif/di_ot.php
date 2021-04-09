<?php

namespace App\Model\Correctif;

use Illuminate\Database\Eloquent\Model;

class di_ot extends Model
{
    protected $guarded = ['OtID','created_at','updated_at'];
    protected $primaryKey = 'OtID';
}
