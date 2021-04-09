<?php

namespace App\Model\Preventif;

use Illuminate\Database\Eloquent\Model;

class prev_bsm extends Model
{
    protected $guarded = ['BsmID','created_at','updated_at'];
    protected $primaryKey = 'BsmID';
}
