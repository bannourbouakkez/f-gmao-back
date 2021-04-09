<?php

namespace App\Model\Divers;

use Illuminate\Database\Eloquent\Model;

class ligne extends Model
{
    protected $guarded = ['LigneID','created_at','updated_at'];
    protected $primaryKey = 'LigneID';
}
