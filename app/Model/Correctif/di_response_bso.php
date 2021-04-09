<?php

namespace App\Model\Correctif;

use Illuminate\Database\Eloquent\Model;

class di_response_bso extends Model
{
    protected $guarded = ['ResponseBsoID','created_at','updated_at'];
    protected $primaryKey = 'ResponseBsoID';
}
