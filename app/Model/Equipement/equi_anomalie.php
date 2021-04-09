<?php

namespace App\Model\Equipement;

use Illuminate\Database\Eloquent\Model;

class equi_anomalie extends Model
{
    protected $guarded = ['AnomalieID','created_at','updated_at'];
    protected $primaryKey = 'AnomalieID';
}
