<?php

namespace App\Model\Achat;

use Illuminate\Database\Eloquent\Model;

class cmd_bon_det extends Model
{
    protected $guarded = ['BonDetID','created_at','updated_at'];
    protected $primaryKey = 'BonDetID';
}
