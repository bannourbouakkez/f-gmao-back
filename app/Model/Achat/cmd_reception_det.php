<?php

namespace App\Model\Achat;

use Illuminate\Database\Eloquent\Model;

class cmd_reception_det extends Model
{
    protected $guarded = ['CmdRecDetID','created_at','updated_at'];
    protected $primaryKey = 'CmdRecDetID';
}
