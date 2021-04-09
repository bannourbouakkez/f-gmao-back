<?php

namespace App\Model\Atelier;

use Illuminate\Database\Eloquent\Model;

class atel_article extends Model
{
    protected $guarded = ['ArticleID','created_at','updated_at'];
    protected $primaryKey = 'ArticleID';
}
