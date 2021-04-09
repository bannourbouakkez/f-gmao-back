<?php

namespace App\Model\Achat;

use Illuminate\Database\Eloquent\Model;

class cmd_reception_modif_det extends Model
{
    protected $guarded = ['CmdRecModifDetID','created_at','updated_at'];
    protected $primaryKey = 'CmdRecModifDetID';
}
