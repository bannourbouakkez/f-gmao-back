<?php

namespace App\Model\Magasin;

use Illuminate\Database\Eloquent\Model;

class art_cara extends Model
{
    protected $guarded = ['CaraID','created_at','updated_at'];
    protected $primaryKey = 'CaraID';
}
