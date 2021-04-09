<?php

namespace App\Model\Divers;

use Illuminate\Database\Eloquent\Model;

class sous_equipement extends Model
{
    protected $guarded = ['SousEquipementID','created_at','updated_at'];
    protected $primaryKey = 'SousEquipementID';
}
