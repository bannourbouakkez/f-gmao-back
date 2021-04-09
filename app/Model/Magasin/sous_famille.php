<?php

namespace App\Model\Magasin;

use Illuminate\Database\Eloquent\Model;

class sous_famille extends Model
{
    protected $guarded = ['SousFamilleID','created_at','updated_at'];
    protected $primaryKey = 'SousFamilleID';
}
