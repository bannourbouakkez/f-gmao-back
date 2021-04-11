<?php

namespace App\Model\Achat;

use Illuminate\Database\Eloquent\Model;

class fournisseur extends Model
{
    //protected $fillable =   ['livmode_id'];
    protected $guarded = ['id','created_at,updated_at'];
    protected $primaryKey = 'id';
    
}
