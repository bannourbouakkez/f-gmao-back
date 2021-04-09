<?php

namespace App\Model\Auth;

use Illuminate\Database\Eloquent\Model;

class post extends Model
{
    protected $guarded = ['PostID','created_at','updated_at'];
    protected $primaryKey = 'PostID';
}
