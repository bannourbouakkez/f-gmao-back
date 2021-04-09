<?php

namespace App\Model\Divers;

use Illuminate\Database\Eloquent\Model;

class articles_perdu extends Model
{
    protected $guarded = ['ArticlePerduID','created_at','updated_at'];
    protected $primaryKey = 'ArticlePerduID';
}
