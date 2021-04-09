<?php

namespace App\Model\Preventif;

use Illuminate\Database\Eloquent\Model;

class prev_bsm_det extends Model
{
    protected $guarded = ['BsmDetID','created_at','updated_at'];
    protected $primaryKey = 'BsmDetID';
}
