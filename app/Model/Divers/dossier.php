<?php

namespace App\Model\Divers;

use Illuminate\Database\Eloquent\Model;

class dossier extends Model
{
    protected $guarded = ['DossierID','created_at,updated_at'];
    protected $primaryKey = 'DossierID';

}
