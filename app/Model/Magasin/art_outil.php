<?php

namespace App\Model\Magasin;

use Illuminate\Database\Eloquent\Model;

class art_outil extends Model
{
    protected $guarded = ['OutilID','created_at','updated_at'];
    protected $primaryKey = 'OutilID';
}
