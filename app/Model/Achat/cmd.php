<?php

namespace App\Model\Achat;

use Illuminate\Database\Eloquent\Model;

class cmd extends Model
{
    protected $guarded = ['CommandeID','created_at','updated_at'];
    protected $primaryKey = 'CommandeID';
}
