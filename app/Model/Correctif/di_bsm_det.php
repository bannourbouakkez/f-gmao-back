<?php

namespace App\Model\Correctif;

use Illuminate\Database\Eloquent\Model;

class di_bsm_det extends Model
{
    protected $guarded = ['BsmDetID','created_at','updated_at'];
    protected $primaryKey = 'BsmDetID';
}
