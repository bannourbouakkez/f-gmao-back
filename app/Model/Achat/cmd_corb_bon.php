<?php

namespace App\Model\Achat;

use Illuminate\Database\Eloquent\Model;

class cmd_corb_bon extends Model
{
    protected $guarded = ['BonID','created_at','updated_at'];
    protected $primaryKey = 'BonID';
}
