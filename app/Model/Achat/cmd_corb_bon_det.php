<?php

namespace App\Model\Achat;

use Illuminate\Database\Eloquent\Model;

class cmd_corb_bon_det extends Model
{
    protected $guarded = ['BonDetID','created_at','updated_at'];
    protected $primaryKey = 'BonDetID';
}
