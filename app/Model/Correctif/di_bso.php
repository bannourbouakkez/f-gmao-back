<?php

namespace App\Model\Correctif;

use Illuminate\Database\Eloquent\Model;

class di_bso extends Model
{
    protected $guarded = ['BsoID','created_at','updated_at'];
    protected $primaryKey = 'BsoID';
}
