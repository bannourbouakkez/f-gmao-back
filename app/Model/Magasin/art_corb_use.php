<?php

namespace App\Model\Magasin;

use Illuminate\Database\Eloquent\Model;

class art_corb_use extends Model
{
    protected $guarded = ['UseID','created_at','updated_at'];
    protected $primaryKey = 'UseID';
}
