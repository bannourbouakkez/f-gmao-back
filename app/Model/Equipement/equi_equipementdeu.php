<?php

namespace App\Model\Equipement;

use Illuminate\Database\Eloquent\Model;

class equi_equipementdeu extends Model
{
    
    protected $guarded = ['EquipementID','created_at','updated_at'];
    protected $primaryKey = 'EquipementID';
    
    /*
    public function equipement(){
    return $this->belongsTo('App\Model\Equipement\equi_equipement', 'EquipementID');
    }
    */

}
