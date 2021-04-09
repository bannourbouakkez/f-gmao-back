<?php

namespace App\Model\Divers;

use Illuminate\Database\Eloquent\Model;

class file extends Model
{
    protected $guarded = ['FileID','created_at,updated_at'];
    protected $primaryKey = 'FileID';
}
