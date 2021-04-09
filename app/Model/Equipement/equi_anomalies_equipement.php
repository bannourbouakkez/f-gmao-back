<?php

namespace App\Model\Equipement;

use Illuminate\Database\Eloquent\Model;

class equi_anomalies_equipement extends Model
{
    protected $guarded = ['AnomalieEquipementID','created_at','updated_at'];
    protected $primaryKey = 'AnomalieEquipementID';
}
