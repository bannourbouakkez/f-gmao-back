<?php

namespace App\Model\Achat;

use Illuminate\Database\Eloquent\Model;

class cmd_rart extends Model
{
    protected $guarded = ['CmdRartID','created_at','updated_at'];
    protected $primaryKey = 'CmdRartID';
}
