<?php

namespace App\Model\Correctif;

use Illuminate\Database\Eloquent\Model;

class di_bsm extends Model
{
    protected $guarded = ['BsmID','created_at','updated_at'];
    protected $primaryKey = 'BsmID';
}
