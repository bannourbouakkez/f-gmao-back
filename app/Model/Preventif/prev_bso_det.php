<?php

namespace App\Model\Preventif;

use Illuminate\Database\Eloquent\Model;

class prev_bso_det extends Model
{
    protected $guarded = ['BsoDetID','created_at','updated_at'];
    protected $primaryKey = 'BsoDetID';
}
