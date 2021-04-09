<?php

namespace App\Model\Divers;

use Illuminate\Database\Eloquent\Model;

class date_time_test extends Model
{
    protected $guarded = ['DateTimeID','created_at','updated_at'];
    protected $primaryKey = 'DateTimeID';
    //$table->timestamp("time");
   // protected $casts = [ 'time' => 'hh:mm'];
}
