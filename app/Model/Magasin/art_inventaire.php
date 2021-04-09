<?php

namespace App\Model\Magasin;

use Illuminate\Database\Eloquent\Model;

class art_inventaire extends Model
{
    protected $guarded = ['InventaireID','created_at','updated_at'];
    protected $primaryKey = 'InventaireID';
}
