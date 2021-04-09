<?php

namespace App\Model\Divers;

use Illuminate\Database\Eloquent\Model;

class intervenant extends Model
{
    protected $guarded = ['IntervenantID','created_at','updated_at'];
    protected $primaryKey = 'IntervenantID';
}
