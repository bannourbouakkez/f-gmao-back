<?php

namespace App\Model\Correctif;

use Illuminate\Database\Eloquent\Model;

class di_response_bso_det extends Model
{
    protected $guarded = ['ResponseBsoDetID','created_at','updated_at'];
    protected $primaryKey = 'ResponseBsoDetID';
}
