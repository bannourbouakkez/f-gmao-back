<?php

namespace App\Model\Preventif;

use Illuminate\Database\Eloquent\Model;

class prev_reservation extends Model
{
    protected $guarded = ['ReservationID','created_at','updated_at'];
    protected $primaryKey = 'ReservationID';
}
