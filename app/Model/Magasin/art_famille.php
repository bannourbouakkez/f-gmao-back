<?php

namespace App\Model\Magasin;

use Illuminate\Database\Eloquent\Model;

class art_famille extends Model
{
    protected $guarded = ['FamilleID','created_at','updated_at'];
    protected $primaryKey = 'FamilleID';
}
