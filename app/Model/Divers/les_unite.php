<?php

namespace App\Model\Divers;

use Illuminate\Database\Eloquent\Model;

class les_unite extends Model
{
    protected $guarded = ['LeUniteID','created_at','updated_at'];
    protected $primaryKey = 'LeUniteID';
}
