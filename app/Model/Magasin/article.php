<?php

namespace App\Model\Magasin;

use Illuminate\Database\Eloquent\Model;

class article extends Model
{
    //protected $fillable =   [''];
    protected $guarded = ['ArticleID','created_at,updated_at'];
    protected $primaryKey = 'ArticleID';
}
