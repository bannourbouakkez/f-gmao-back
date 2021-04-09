<?php

namespace App\Model\Preventif;

use Illuminate\Database\Eloquent\Model;

class prev_reservedintervenant extends Model
{
    protected $guarded = ['IntReservationID','created_at','updated_at'];
    protected $primaryKey = 'IntReservationID';
}
