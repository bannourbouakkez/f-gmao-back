<?php

namespace App\Model\Achat;

use Illuminate\Database\Eloquent\Model;

class cmd_daarticle extends Model
{
    protected $guarded = ['CmdDaArticleID','created_at','updated_at'];
    protected $primaryKey = 'CmdDaArticleID';
}
