<?php

namespace App\Model\Magasin;

use Illuminate\Database\Eloquent\Model;

class art_fam_cara extends Model
{
    protected $guarded = ['FamCaraID','created_at','updated_at'];
    protected $primaryKey = 'FamCaraID';
}
