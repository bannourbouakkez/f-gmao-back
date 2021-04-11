<?php

namespace App\Model\Achat;

use Illuminate\Database\Eloquent\Model;

class fournisseur extends Model
{
    protected $fillable =  [];
    //protected $fillable =  ['nom','livmode_id','secteur_id','dossier_id','ref','TVA','abr','adresse','tel1','tel2','portable1','portable2','fax1','fax2','email1','email2','siteweb','fraisliv','autresfrais','remise','cond_regl','remarques'];
    protected $guarded = ['id','created_at','updated_at'];
    protected $primaryKey = 'id';
    
}
