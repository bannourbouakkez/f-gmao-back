<?php

namespace App\Model\Correctif;

use Illuminate\Database\Eloquent\Model;

class di_di extends Model
{
    protected $guarded = ['DiID','created_at','updated_at'];
    protected $primaryKey = 'DiID';
}
