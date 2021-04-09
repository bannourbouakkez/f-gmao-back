<?php

namespace App\Model\Preventif;

use Illuminate\Database\Eloquent\Model;

class prev_response_bsm_det extends Model
{
    protected $guarded = ['ResponseBsmDetID','created_at','updated_at'];
    protected $primaryKey = 'ResponseBsmDetID';
}
