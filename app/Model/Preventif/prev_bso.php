<?php

namespace App\Model\Preventif;

use Illuminate\Database\Eloquent\Model;

class prev_bso extends Model
{
    protected $guarded = ['BsoID','created_at','updated_at'];
    protected $primaryKey = 'BsoID';
}
