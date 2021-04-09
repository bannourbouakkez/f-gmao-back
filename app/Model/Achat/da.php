<?php

namespace App\Model\Achat;

use Illuminate\Database\Eloquent\Model;

class da extends Model
{
    //protected $fillable =   [''];
    protected $guarded = ['id'];
    protected $primaryKey = 'id';


/*
    public static function getEditionsByUser($userId)
    {
        $tableau = User::with('editions')->where('id',$userId)->get();
        return $tableau;
    }
*/


}
