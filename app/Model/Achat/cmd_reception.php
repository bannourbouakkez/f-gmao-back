<?php

namespace App\Model\Achat;

use Illuminate\Database\Eloquent\Model;

class cmd_reception extends Model
{
    protected $guarded = ['CmdRecID','created_at','updated_at'];
    protected $primaryKey = 'CmdRecID';
}
