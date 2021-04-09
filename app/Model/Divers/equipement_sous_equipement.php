<?php

namespace App\Model\Divers;

use Illuminate\Database\Eloquent\Model;

class equipement_sous_equipement extends Model
{
    protected $guarded = ['EquipementSousEquipementID','created_at','updated_at'];
    protected $primaryKey = 'EquipementSousEquipementID';
}
