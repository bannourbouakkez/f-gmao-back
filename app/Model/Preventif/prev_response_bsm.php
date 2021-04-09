<?php

namespace App\Model\Preventif;

use Illuminate\Database\Eloquent\Model;

class prev_response_bsm extends Model
{
    protected $guarded = ['ResponseBsmID','created_at','updated_at'];
    protected $primaryKey = 'ResponseBsmID';
}
