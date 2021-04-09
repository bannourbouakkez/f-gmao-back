<?php

namespace App\Model\Preventif;

use Illuminate\Database\Eloquent\Model;

class prev_reservation_det extends Model
{
    protected $guarded = ['ReservationDetID','created_at','updated_at'];
    protected $primaryKey = 'ReservationDetID';
}
