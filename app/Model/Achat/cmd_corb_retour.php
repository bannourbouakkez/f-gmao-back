<?php

namespace App\Model\Achat;

use Illuminate\Database\Eloquent\Model;

class cmd_corb_retour extends Model
{
    protected $guarded = ['RetourID','created_at','updated_at'];
    protected $primaryKey = 'RetourID';
}
