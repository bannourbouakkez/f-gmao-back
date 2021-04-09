<?php

namespace App\Model\Magasin;

use Illuminate\Database\Eloquent\Model;

class art_pp extends Model
{
    protected $guarded = ['PumpID','created_at','updated_at'];
    protected $primaryKey = 'PumpID';
}
