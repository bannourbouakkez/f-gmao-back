<?php

namespace App\Model\Achat;

use Illuminate\Database\Eloquent\Model;

class rart extends Model
{
    //protected $fillable =   [''];
    protected $guarded = ['id','created_at,updated_at'];
    protected $primaryKey = 'id';
}
