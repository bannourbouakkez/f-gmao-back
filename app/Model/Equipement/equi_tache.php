<?php

namespace App\Model\Equipement;

use Illuminate\Database\Eloquent\Model;

class equi_tache extends Model
{
    protected $guarded = ['created_at','updated_at'];
    protected $primaryKey = 'TacheID';
}
