<?php

namespace App\Model\Auth;

use Illuminate\Database\Eloquent\Model;

class user_post extends Model
{
    protected $guarded = ['UserPostID','created_at','updated_at'];
    protected $primaryKey = 'UserPostID';
}
