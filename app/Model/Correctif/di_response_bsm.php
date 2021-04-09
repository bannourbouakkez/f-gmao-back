<?php

namespace App\Model\Correctif;

use Illuminate\Database\Eloquent\Model;

class di_response_bsm extends Model
{
    protected $guarded = ['ResponseBsmID','created_at','updated_at'];
    protected $primaryKey = 'ResponseBsmID';
}
