<?php

namespace App\Model\Equipement;

use Illuminate\Database\Eloquent\Model;

class equi_equipement extends Model
{
    protected $guarded = ['EquipementID','created_at','updated_at'];
    protected $primaryKey = 'EquipementID';

    public function equipementdeu()
    {
        return $this->hasOne('App\Model\Equipement\equi_equipementdeu','EquipementID');
    }

}
