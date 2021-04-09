<?php

namespace App\Model\Equipement;

use Illuminate\Database\Eloquent\Model;

class equi_niveau extends Model
{
    protected $guarded = ['NiveauID','created_at','updated_at'];
    protected $primaryKey = 'NiveauID';
}
