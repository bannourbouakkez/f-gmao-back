<?php

namespace App\Model\Correctif;

use Illuminate\Database\Eloquent\Model;

class di_response_bsm_det extends Model
{
    protected $guarded = ['ResponseBsmDetID','created_at','updated_at'];
    protected $primaryKey = 'ResponseBsmDetID';
}
