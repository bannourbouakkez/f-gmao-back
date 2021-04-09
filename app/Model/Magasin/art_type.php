<?php

namespace App\Model\Magasin;

use Illuminate\Database\Eloquent\Model;

class art_type extends Model
{
    protected $guarded = ['TypeID','created_at','updated_at'];
    protected $primaryKey = 'TypeID';
}
