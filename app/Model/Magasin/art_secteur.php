<?php

namespace App\Model\Magasin;

use Illuminate\Database\Eloquent\Model;

class art_secteur extends Model
{
    protected $guarded = ['SecteurID','created_at','updated_at'];
    protected $primaryKey = 'SecteurID';
    
}
