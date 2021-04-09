<?php

namespace App\Model\Magasin;

use Illuminate\Database\Eloquent\Model;

class art_fam_cara_det extends Model
{
    protected $guarded = ['FamCaraDetID','created_at','updated_at'];
    protected $primaryKey = 'FamCaraDetID';
}
