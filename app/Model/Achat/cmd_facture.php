<?php

namespace App\Model\Achat;

use Illuminate\Database\Eloquent\Model;

class cmd_facture extends Model
{
    protected $guarded = ['FactureID','created_at','updated_at'];
    protected $primaryKey = 'FactureID';
}
