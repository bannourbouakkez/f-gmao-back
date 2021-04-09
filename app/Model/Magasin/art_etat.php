<?php

namespace App\Model\Magasin;

use Illuminate\Database\Eloquent\Model;

class art_etat extends Model
{
    protected $guarded = ['EtatID','created_at','updated_at'];
    protected $primaryKey = 'EtatID';
}
