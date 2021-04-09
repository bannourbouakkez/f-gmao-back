<?php

namespace App\Model\Magasin;

use Illuminate\Database\Eloquent\Model;

class art_emplacement extends Model
{

    protected $guarded = ['EmplacementID','created_at','updated_at'];
    protected $primaryKey = 'EmplacementID';
}
