<?php

namespace App\Model\Magasin;

use Illuminate\Database\Eloquent\Model;

class art_inventaire_det extends Model
{
    protected $guarded = ['InventaireDetID','created_at','updated_at'];
    protected $primaryKey = 'InventaireDetID';
}
