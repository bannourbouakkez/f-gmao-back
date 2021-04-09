<?php

namespace App\Model\Achat;

use Illuminate\Database\Eloquent\Model;

class cmd_corb_retour_det extends Model
{
    protected $guarded = ['RetourDetID','created_at','updated_at'];
    protected $primaryKey = 'RetourDetID';
}
