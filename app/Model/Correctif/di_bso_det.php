<?php

namespace App\Model\Correctif;

use Illuminate\Database\Eloquent\Model;

class di_bso_det extends Model
{
    protected $guarded = ['BsoDetID','created_at','updated_at'];
    protected $primaryKey = 'BsoDetID';
}
