<?php

namespace App\Model\Preventif;

use Illuminate\Database\Eloquent\Model;

class prev_otp extends Model
{
    protected $guarded = ['OtpID','created_at','updated_at'];
    protected $primaryKey = 'OtpID';
}
