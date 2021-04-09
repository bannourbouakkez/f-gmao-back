<?php

namespace App\Model\Divers;

use Illuminate\Database\Eloquent\Model;

class equipement extends Model
{
    protected $guarded = ['EquipementID','created_at','updated_at'];
    protected $primaryKey = 'EquipementID';
}
