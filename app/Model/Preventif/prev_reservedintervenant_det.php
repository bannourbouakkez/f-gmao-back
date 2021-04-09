<?php

namespace App\Model\Preventif;

use Illuminate\Database\Eloquent\Model;

class prev_reservedintervenant_det extends Model
{
    protected $guarded = ['ReservationIntervenantID','created_at','updated_at'];
    protected $primaryKey = 'ReservationIntervenantID';
}
