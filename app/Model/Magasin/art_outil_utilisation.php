<?php

namespace App\Model\Magasin;

use Illuminate\Database\Eloquent\Model;

class art_outil_utilisation extends Model
{
    protected $guarded = ['OutilUtilisationsID','created_at','updated_at'];
    protected $primaryKey = 'OutilUtilisationsID';
}
