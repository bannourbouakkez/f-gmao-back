<?php

namespace App\Model\Magasin;

use Illuminate\Database\Eloquent\Model;

class art_article_cara extends Model
{
    protected $guarded = ['ArtCaraID','created_at','updated_at'];
    protected $primaryKey = 'ArtCaraID';
}
