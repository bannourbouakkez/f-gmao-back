<?php

namespace App\Model\Equipement;

use Illuminate\Database\Eloquent\Model;

class equi_magasin_article extends Model
{
    protected $guarded = ['EquipementArticleID','created_at','updated_at'];
    protected $primaryKey = 'EquipementArticleID';
}
