<?php

namespace App\Model\Magasin;

use Illuminate\Database\Eloquent\Model;

class art_utilisation extends Model
{
    protected $guarded = ['UtilisationID','created_at','updated_at'];
    protected $primaryKey = 'UtilisationID';
}
