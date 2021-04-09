<?php

namespace App\Model\Equipement;

use Illuminate\Database\Eloquent\Model;

class equi_taches_equipement extends Model
{
    protected $guarded = ['TacheEquipementID','created_at','updated_at'];
    protected $primaryKey = 'TacheEquipementID';
}
