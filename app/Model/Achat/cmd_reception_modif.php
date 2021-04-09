<?php

namespace App\Model\Achat;

use Illuminate\Database\Eloquent\Model;

class cmd_reception_modif extends Model
{
    protected $guarded = ['CmdRecModifID','created_at','updated_at'];
    protected $primaryKey = 'CmdRecModifID';
}
