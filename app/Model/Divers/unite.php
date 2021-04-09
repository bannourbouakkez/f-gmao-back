<?php

namespace App\Model\Divers;

use Illuminate\Database\Eloquent\Model;

class unite extends Model
{
    protected $guarded = ['UniteID','created_at','updated_at'];
    protected $primaryKey = 'UniteID';
}
