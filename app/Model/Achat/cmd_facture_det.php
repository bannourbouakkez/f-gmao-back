<?php

namespace App\Model\Achat;

use Illuminate\Database\Eloquent\Model;

class cmd_facture_det extends Model
{
    protected $guarded = ['FacDetID','created_at','updated_at'];
    protected $primaryKey = 'FacDetID';
}
