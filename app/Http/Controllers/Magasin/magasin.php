<?php

namespace App\Http\Controllers\Magasin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Carbon\Carbon;
use DateTime;

use Illuminate\Support\Facades\DB;

use App\Model\Correctif\di_di;
use App\Model\Correctif\di_ot;
use App\Model\Correctif\di_ot_intervenant;

use App\Model\Preventif\prev_intervention;
use App\Model\Preventif\prev_otp;
use App\Model\Preventif\prev_response_bsm;
use App\Model\Preventif\prev_response_bsm_det;
use App\Model\Preventif\prev_bsm;
use App\Model\Preventif\prev_bsm_det;

use App\Model\Correctif\di_bsm;
use App\Model\Correctif\di_bsm_det;
use App\Model\Correctif\di_response_bsm;
use App\Model\Correctif\di_response_bsm_det;

use App\Model\Correctif\di_bso;
use App\Model\Correctif\di_bso_det;
use App\Model\Correctif\di_response_bso;
use App\Model\Correctif\di_response_bso_det;

use App\Model\Equipement\equi_equipement;

use App\Model\Magasin\art_outil;
use App\Model\Magasin\art_2_use;
use App\Model\Divers\intervenant;

use App\Model\Correctif\di_bon_retour;
use App\Model\Correctif\di_bon_retour_det;
use App\Model\Correctif\di_bon_retour_hist;


use App\Model\Preventif\prev_bon_retour;
use App\Model\Preventif\prev_bon_retour_det;
use App\Model\Preventif\prev_bon_retour_hist;



use App\User;
use JWTFactory;
use JWTAuth;
use stdClass;

class magasin extends Controller
{
    
     
public function DateFormYMD($date){ $date = Carbon::parse($date); $date= $date->format('Y-m-d'); return $date;}
public function TimeFormHM($time){ $time = Carbon::parse($time); $time=$time->format('H:i'); return $time; }


    public function  getBsm(Request $request,$id){
        
        
        $Src=$request->Src;
        if($Src=='correctif'){

        $bsm=di_bsm::where('BsmID','=',$id)
        ->join('users', function ($join) {
          $join->on('users.id', '=', 'di_bsms.user_id');
        })
        ->select('di_bsms.*','users.name')
        ->first();

        $bsm_det=di_bsm_det::where('bsm_id','=',$id)
        ->join('articles', function ($join) {
            $join->on('articles.ArticleID', '=', 'di_bsm_dets.article_id');
        })
        ->select('di_bsm_dets.*','articles.des')
        ->get();

        $nv_bsm_det=Array();

        $EquipementID=$this->GetEquipementIDByOtID($bsm->ot_id);
        $equi_equipement=equi_equipement::where('EquipementID','=',$EquipementID)->first();
        $BG=$equi_equipement->BG; $BD=$equi_equipement->BD;

        $niveaus=app('App\Http\Controllers\Equipement\equipement')->getNiveausCompletDetByEquipementID($EquipementID);
        $niveaus=$niveaus->original;

        $recepteur_user_id=$this->GetRecepteurIDByBsmID($id);
        foreach($bsm_det as $article){
          
          //$BsmDetID=$article['BsmDetID'];
          $article_id=$article['article_id'];
          //$article_id=1;
          
          $res=di_response_bsm_det::
          join('di_bsm_dets', function ($join) use($article_id) {
            $join->on('di_bsm_dets.BsmDetID', '=', 'di_response_bsm_dets.bsm_det_id')
             ->where('di_bsm_dets.article_id','=',$article_id)
            ;
          })

          ->join('di_response_bsms', function ($join) {
            $join->on('di_response_bsms.ResponseBsmID', '=', 'di_response_bsm_dets.response_bsm_id');
          })

          ->join('di_bsms', function ($join) {
            $join->on('di_bsms.BsmID', '=', 'di_response_bsms.bsm_id');
          })
          ->join('di_ots', function ($join) {
            $join->on('di_ots.OtID', '=', 'di_bsms.ot_id');
          })
          ->join('di_dis', function ($join) use($recepteur_user_id) {
            $join->on('di_dis.DiID', '=', 'di_ots.di_id');
            //->where('di_dis.recepteur_user_id','=',$recepteur_user_id);
          })
          ->join('equi_equipements', function ($join) use($BG,$BD) {
            $join->on('equi_equipements.EquipementID', '=', 'di_dis.equipement_id')
                 ->where('equi_equipements.BG','>=',$BG)
                 ->where('equi_equipements.BD','<=',$BD)
                 ->where('equi_equipements.exist','=',1);
          })
          ->join('users', function ($join) use($recepteur_user_id) {
            $join->on('users.id', '=', 'di_dis.recepteur_user_id');
            //->where('di_dis.recepteur_user_id','=',$recepteur_user_id);
          })
          
          //->select('di_dis.recepteur_user_id')
          //->distinct('di_dis.recepteur_user_id')
          ->select('di_response_bsm_dets.*','di_ots.OtID','di_dis.DiID','equi_equipements.equipement','users.name')
          ->orderBy('di_response_bsm_dets.created_at', 'desc')
          
          ->first();
          //$recepteur_user_id=$recepteur_user_id->recepteur_user_id;
          
          $article->LastUse=$res;
          array_push($nv_bsm_det,$article);

          //return response()->json($res);
          
        }
        
        //return response()->json($recepteur_user_id);

        return response()->json( [ 'bsm'=>$bsm ,'niveaus'=>$niveaus,'bsm_det'=>$nv_bsm_det ] );
        
      }


      if($Src=='preventif'){

        $bsm=prev_bsm::where('BsmID','=',$id)
        ->join('users', function ($join) {
          $join->on('users.id', '=', 'prev_bsms.user_id');
        })
        ->select('prev_bsms.*','users.name')
        ->first();

        $bsm_det=prev_bsm_det::where('bsm_id','=',$id)
        ->join('articles', function ($join) {
            $join->on('articles.ArticleID', '=', 'prev_bsm_dets.article_id');
        })
        ->select('prev_bsm_dets.*','articles.des')
        ->get();

        $nv_bsm_det=Array();

        $EquipementID=$this->GetEquipementIDByOtpID($bsm->otp_id);
        $equi_equipement=equi_equipement::where('EquipementID','=',$EquipementID)->first();
        $BG=$equi_equipement->BG; $BD=$equi_equipement->BD;

        $niveaus=app('App\Http\Controllers\Equipement\equipement')->getNiveausCompletDetByEquipementID($EquipementID);
        $niveaus=$niveaus->original;

        //$recepteur_user_id=$this->GetRecepteurIDByBsmID($id);
        foreach($bsm_det as $article){
          
          //$BsmDetID=$article['BsmDetID'];
          $article_id=$article['article_id'];
          //$article_id=1;
          
          $res=prev_response_bsm_det::
          join('prev_bsm_dets', function ($join) use($article_id) {
            $join->on('prev_bsm_dets.BsmDetID', '=', 'prev_response_bsm_dets.bsm_det_id')
             ->where('prev_bsm_dets.article_id','=',$article_id)
            ;
          })

          ->join('prev_response_bsms', function ($join) {
            $join->on('prev_response_bsms.ResponseBsmID', '=', 'prev_response_bsm_dets.response_bsm_id');
          })

          ->join('prev_bsms', function ($join) {
            $join->on('prev_bsms.BsmID', '=', 'prev_response_bsms.bsm_id');
          })
          ->join('prev_otps', function ($join) {
            $join->on('prev_otps.OtpID', '=', 'prev_bsms.otp_id');
          })
          ->join('prev_interventions', function ($join) {
            $join->on('prev_interventions.InterventionID', '=', 'prev_otps.intervention_id');
          })
          ->join('equi_equipements', function ($join) use($BG,$BD) {
            $join->on('equi_equipements.EquipementID', '=', 'prev_interventions.equipement_id')
                 ->where('equi_equipements.BG','>=',$BG)
                 ->where('equi_equipements.BD','<=',$BD)
                 ->where('equi_equipements.exist','=',1);
          })
          ->join('users', function ($join)  {
            $join->on('users.id', '=', 'prev_otps.user_id');
            //->where('di_dis.recepteur_user_id','=',$recepteur_user_id);
          })
          
          //->select('di_dis.recepteur_user_id')
          //->distinct('di_dis.recepteur_user_id')
          ->select('prev_response_bsm_dets.*','prev_otps.OtpID','equi_equipements.equipement','users.name')
          ->orderBy('prev_response_bsm_dets.created_at', 'desc')
          
          ->first();
          //$recepteur_user_id=$recepteur_user_id->recepteur_user_id;
          
          $article->LastUse=$res;
          array_push($nv_bsm_det,$article);

          //return response()->json($res);
          
        }
        
        //return response()->json($recepteur_user_id);

        return response()->json( [ 'bsm'=>$bsm ,'niveaus'=>$niveaus,'bsm_det'=>$nv_bsm_det ] );
        
      }



    }

    public function getBso(Request $request,$id){
      
      $Src=$request->Src;

      if($Src=='correctif'){

      $bso=di_bso::where('BsoID','=',$id)
      ->join('di_ots', function ($join) {
        $join->on('di_ots.OtID', '=', 'di_bsos.ot_id'); // fil intithar ama 7kéya o5ra // c bon 
      })
      ->join('di_dis', function ($join) {
        $join->on('di_dis.DiID', '=', 'di_ots.di_id');
      })
      ->join('users as recepteur', function ($join) {
        $join->on('recepteur.id', '=', 'di_dis.recepteur_user_id');
      })
      ->join('users', function ($join) {
        $join->on('users.id', '=', 'di_bsos.user_id');
      })
      ->select('di_bsos.*','users.name','di_dis.recepteur_user_id','recepteur.name as recepteur')
      ->first();
      }

      if($Src=='preventif'){

        $bso=di_bso::where('BsoID','=',$id)
        ->join('prev_otps', function ($join) {
          $join->on('prev_otps.OtpID', '=', 'di_bsos.ot_id'); 
        })
        ->join('users as recepteur', function ($join) {
          $join->on('recepteur.id', '=', 'prev_otps.user_id');
        })
        ->join('users', function ($join) {
          $join->on('users.id', '=', 'di_bsos.user_id');
        })
        ->select('di_bsos.*','users.name','prev_otps.user_id as recepteur_user_id','recepteur.name as recepteur')
        ->first();
  
        }


      $bso_det=di_bso_det::where('bso_id','=',$id)
      ->join('art_outils', function ($join) {
          $join->on('art_outils.OutilID', '=', 'di_bso_dets.outil_id')
          ->where('art_outils.exist','=',1);
      })
      ->select('di_bso_dets.*','art_outils.des','art_outils.reserve')
      ->get();
     
      return response()->json( [ 'bso'=>$bso ,'bso_det'=>$bso_det ] );
 
    }

    public function getBsos(Request $request){

      $page=$request->input('page');
      $itemsPerPage=$request->input('itemsPerPage');
      $nodes = $request->input('nodes');
      
      $skipped = ($page - 1) * $itemsPerPage;
      $endItem = $skipped + $itemsPerPage;
      
      $filter=$request->input('filter');
      $datemin=$filter['datemin'];
      $datemax=$filter['datemax'];
      $datemin=app('App\Http\Controllers\Divers\generale')->ReglageDateMinDateMax($datemin,'datemin');
      $datemax=app('App\Http\Controllers\Divers\generale')->ReglageDateMinDateMax($datemax,'datemax');
      $searchFilterText=$filter['searchFilterText'];
      
      $type=$filter['type'];
      $statut=$filter['statut'];
      
      $UserID=JWTAuth::user()->id;
      $me=app('App\Http\Controllers\Divers\generale')->getUserPosts($UserID);
      $me=$me->original; $me=$me[0];
      $posts=$me->posts;
      
      $isAdmin=app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts,['Admin']);
      $isMethode=app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts,['Methode']);
      $OthersAuthorized=['ResponsableMagasin'];
      $isOthersAuthorized=app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts,$OthersAuthorized);
      
      $isBso=in_array("bso",$type);
      $isUse=in_array("use",$type);

      if($isBso){
      $bsos_correctif = di_bso::
        where('di_bsos.isCorrectif','=',1)
      ->whereDate('di_bsos.created_at','>=',$datemin)
      ->whereDate('di_bsos.created_at','<=',$datemax)
      ->WhereIn('di_bsos.statut',$statut)
      ->join('users', function ($join) {
         $join->on('users.id', '=', 'di_bsos.user_id');
      })
      ->join('di_ots', function ($join){
        $join->on('di_ots.OtID', '=', 'di_bsos.ot_id');
      })
      ->join('di_dis', function ($join) {
        $join->on('di_dis.DiID', '=', 'di_ots.di_id');
      })
      ->join('equi_equipements', function ($join) use ($nodes){
        if(count($nodes)==0){
          $join->on('equi_equipements.EquipementID', '=', 'di_dis.equipement_id');
        }else{
          $join->on('equi_equipements.EquipementID', '=', 'di_dis.equipement_id')
               ->whereIn('equi_equipements.EquipementID', $nodes);
        }
      })
      ->Where(function ($query) use($searchFilterText){
         return $query   
               ->Where('users.name','like','%'.$searchFilterText.'%')
               ->orWhere('equi_equipements.equipement','like','%'.$searchFilterText.'%');
      })
      ->select('users.name','di_bsos.*','equi_equipements.equipement')
      ->addSelect(DB::raw("'null' as date_cloture"))
      ->addSelect(DB::raw("'null' as intervenant_name"))
      ->addSelect(DB::raw("'null' as UseID"));

    }else{
       // elli fil else kol just copier coller lel fou9ani 
      // el importnat houwa el condition el mosta7ila  where('di_bsos.isCorrectif','=',11)

      $bsos_correctif = di_bso::
        where('di_bsos.isCorrectif','=',11)
      ->whereDate('di_bsos.created_at','>=',$datemin)
      ->whereDate('di_bsos.created_at','<=',$datemax)
      ->WhereIn('di_bsos.statut',$statut)
      ->join('users', function ($join) {
         $join->on('users.id', '=', 'di_bsos.user_id');
      })
      ->join('di_ots', function ($join){
        $join->on('di_ots.OtID', '=', 'di_bsos.ot_id');
      })
      ->join('di_dis', function ($join) {
        $join->on('di_dis.DiID', '=', 'di_ots.di_id');
      })
      ->join('equi_equipements', function ($join) use ($nodes){
        if(count($nodes)==0){
          $join->on('equi_equipements.EquipementID', '=', 'di_dis.equipement_id');
        }else{
          $join->on('equi_equipements.EquipementID', '=', 'di_dis.equipement_id')
               ->whereIn('equi_equipements.EquipementID', $nodes);
        }
      })
      ->Where(function ($query) use($searchFilterText){
         return $query   
               ->Where('users.name','like','%'.$searchFilterText.'%')
               ->orWhere('equi_equipements.equipement','like','%'.$searchFilterText.'%');
      })
      ->select('users.name','di_bsos.*','equi_equipements.equipement')
      ->addSelect(DB::raw("'null' as date_cloture"))
      ->addSelect(DB::raw("'null' as intervenant_name"))
      ->addSelect(DB::raw("'null' as UseID"));
    }

      if($isBso){
      $bsos_preventif = di_bso:: // fil intithar complique
        where('di_bsos.isCorrectif','=',0)
      ->whereDate('di_bsos.created_at','>=',$datemin)
      ->whereDate('di_bsos.created_at','<=',$datemax)
      ->WhereIn('di_bsos.statut',$statut)
      ->join('users', function ($join) {
         $join->on('users.id', '=', 'di_bsos.user_id');
      })
      ->join('prev_otps', function ($join){
        $join->on('prev_otps.OtpID', '=', 'di_bsos.ot_id');
      })
      ->join('prev_interventions', function ($join) {
        $join->on('prev_interventions.InterventionID', '=', 'prev_otps.intervention_id');
      })
      ->join('equi_equipements', function ($join) use ($nodes){
        if(count($nodes)==0){
          $join->on('equi_equipements.EquipementID', '=', 'prev_interventions.equipement_id');
        }else{
          $join->on('equi_equipements.EquipementID', '=', 'prev_interventions.equipement_id')
               ->whereIn('equi_equipements.EquipementID', $nodes);
        }
      })
      ->Where(function ($query) use($searchFilterText){
         return $query   
               ->Where('users.name','like','%'.$searchFilterText.'%')
               ->orWhere('equi_equipements.equipement','like','%'.$searchFilterText.'%');
      })
      ->select('users.name','di_bsos.*','equi_equipements.equipement')
      ->addSelect(DB::raw("'null' as date_cloture"))
      ->addSelect(DB::raw("'null' as intervenant_name"))
      ->addSelect(DB::raw("'null' as UseID"));
     }else{

      // elli fil else kol just copier coller lel fou9ani 
      // el importnat houwa el condition el mosta7ila  where('di_bsos.isCorrectif','=',11)
      $bsos_preventif = di_bso:: 
        where('di_bsos.isCorrectif','=',11)
      ->whereDate('di_bsos.created_at','>=',$datemin)
      ->whereDate('di_bsos.created_at','<=',$datemax)
      ->WhereIn('di_bsos.statut',$statut)
      ->join('users', function ($join) {
         $join->on('users.id', '=', 'di_bsos.user_id');
      })
      ->join('prev_otps', function ($join){
        $join->on('prev_otps.OtpID', '=', 'di_bsos.ot_id');
      })
      ->join('prev_interventions', function ($join) {
        $join->on('prev_interventions.InterventionID', '=', 'prev_otps.intervention_id');
      })
      ->join('equi_equipements', function ($join) use ($nodes){
        if(count($nodes)==0){
          $join->on('equi_equipements.EquipementID', '=', 'prev_interventions.equipement_id');
        }else{
          $join->on('equi_equipements.EquipementID', '=', 'prev_interventions.equipement_id')
               ->whereIn('equi_equipements.EquipementID', $nodes);
        }
      })
      ->Where(function ($query) use($searchFilterText){
         return $query   
               ->Where('users.name','like','%'.$searchFilterText.'%')
               ->orWhere('equi_equipements.equipement','like','%'.$searchFilterText.'%');
      })
      ->select('users.name','di_bsos.*','equi_equipements.equipement')
      ->addSelect(DB::raw("'null' as date_cloture"))
      ->addSelect(DB::raw("'null' as intervenant_name"))
      ->addSelect(DB::raw("'null' as UseID"));

     }
      
      if($isUse){
      $bsos_use = di_bso:: // fil intithar complique
        where('di_bsos.ot_id','=',NULL)
      ->whereDate('di_bsos.created_at','>=',$datemin)
      ->whereDate('di_bsos.created_at','<=',$datemax)
      ->WhereIn('di_bsos.statut',$statut)
      ->join('users', function ($join) {
         $join->on('users.id', '=', 'di_bsos.user_id');
      })
      ->join('art_2_uses', function ($join) {
        $join->on('art_2_uses.bso_id', '=', 'di_bsos.BsoID');
      })
      ->join('equi_equipements', function ($join) use ($nodes){
        if(count($nodes)==0){
          $join->on('equi_equipements.EquipementID', '=', 'art_2_uses.equipement_id');
        }else{
          $join->on('equi_equipements.EquipementID', '=', 'art_2_uses.equipement_id')
               ->whereIn('equi_equipements.EquipementID', $nodes);
        }
      })
      ->join('intervenants', function ($join) {
        $join->on('intervenants.IntervenantID', '=', 'art_2_uses.intervenant_id');
      })
      ->Where(function ($query) use($searchFilterText){
         return $query   
               ->Where('users.name','like','%'.$searchFilterText.'%')
               ->orWhere('equi_equipements.equipement','like','%'.$searchFilterText.'%')
               ->orWhere('intervenants.name','like','%'.$searchFilterText.'%');
      })

      ->select('users.name','di_bsos.*','equi_equipements.equipement','art_2_uses.UseID','art_2_uses.date_cloture','intervenants.name as intervenant_name') ;
    }else{


      $bsos_use = di_bso:: 
        where('di_bsos.ot_id','=',-1)
      ->whereDate('di_bsos.created_at','>=',$datemin)
      ->whereDate('di_bsos.created_at','<=',$datemax)
      ->WhereIn('di_bsos.statut',$statut)
      ->join('users', function ($join) {
         $join->on('users.id', '=', 'di_bsos.user_id');
      })
      ->join('art_2_uses', function ($join) {
        $join->on('art_2_uses.bso_id', '=', 'di_bsos.BsoID');
      })
      ->join('equi_equipements', function ($join) use ($nodes){
        if(count($nodes)==0){
          $join->on('equi_equipements.EquipementID', '=', 'art_2_uses.equipement_id');
        }else{
          $join->on('equi_equipements.EquipementID', '=', 'art_2_uses.equipement_id')
               ->whereIn('equi_equipements.EquipementID', $nodes);
        }
      })
      ->join('intervenants', function ($join) {
        $join->on('intervenants.IntervenantID', '=', 'art_2_uses.intervenant_id');
      })
      ->Where(function ($query) use($searchFilterText){
         return $query   
               ->Where('users.name','like','%'.$searchFilterText.'%')
               ->orWhere('equi_equipements.equipement','like','%'.$searchFilterText.'%')
               ->orWhere('intervenants.name','like','%'.$searchFilterText.'%');
      })

      ->select('users.name','di_bsos.*','equi_equipements.equipement','art_2_uses.UseID','art_2_uses.date_cloture','intervenants.name as intervenant_name') ;

    }
      $bsos_use
      ->union($bsos_preventif)
      ->union($bsos_correctif);



      $bsos_use->orderBy('created_at', 'desc');

      $bsos=$bsos_use;

      //->orderBy('di_bsos.created_at', 'desc');
      
      /*
      $bsos = di_bso::
          where('di_bsos.isCorrectif','<>',0)
        ->whereDate('di_bsos.created_at','>=',$datemin)
      ->whereDate('di_bsos.created_at','<=',$datemax)
      ->WhereIn('di_bsos.statut',$statut)
      ->Where(function ($query) use($isBso,$isUse){
        if (!$isBso && !$isUse) { return $query ->where('ot_id','<',0); }
        if ($isBso && $isUse) { return $query;}
        if ($isBso && !$isUse ) { return $query ->where('ot_id','>',0); }
        if (!$isBso && $isUse ) { return $query ->where('ot_id','=',null); }
      })
      //->select('users.name','di_bsos.*','equi_equipements.equipement','art_2_uses.UseID','art_2_uses.date_cloture','intervenants.name as intervenant_name') //,'use_equipement.equipement as use_equipement')
      ->join('users', function ($join) {
        $join->on('users.id', '=', 'di_bsos.user_id');
     })

     
     ->leftjoin('di_ots', function ($join){
       $join->on('di_ots.OtID', '=', 'di_bsos.ot_id');
     })
     ->leftjoin('di_dis', function ($join) {
       $join->on('di_dis.DiID', '=', 'di_ots.di_id');
     })
     
     ->leftjoin('art_2_uses', function ($join) {
       $join->on('art_2_uses.bso_id', '=', 'di_bsos.BsoID');
     })

     
     ->leftjoin('equi_equipements', function ($join) use ($nodes){
       if(count($nodes)==0){
         $join->on('equi_equipements.EquipementID', '=', 'di_dis.equipement_id')
              ->orOn('equi_equipements.EquipementID', '=', 'art_2_uses.equipement_id');

       }else{
         
         $join->on('equi_equipements.EquipementID', '=', 'di_dis.equipement_id')
              ->whereIn('equi_equipements.EquipementID', $nodes)
              //->where('di_dis.equipement_id','<>',null)
              //->where('art_2_uses.equipement_id','<>',null)
              ->orOn('equi_equipements.EquipementID', '=', 'art_2_uses.equipement_id')
              ->whereIn('equi_equipements.EquipementID', $nodes);
              //->where('art_2_uses.equipement_id','<>',null)
              //->where('di_dis.equipement_id','<>',null);
       }
     })
     ->where('equi_equipements.equipement','<>',null)

     
     ->select('di_bsos.*');
    */


      $countQuery = $bsos->count();
      $bsos = $bsos->skip($skipped)->take($itemsPerPage)->get();
      return response()->json(['bsos'=>$bsos,'me'=>$me,'total'=>$countQuery]);
  
    }



    public function getBsms(Request $request){

      $page=$request->input('page');
      $itemsPerPage=$request->input('itemsPerPage');
      $nodes = $request->input('nodes');
      
      $skipped = ($page - 1) * $itemsPerPage;
      $endItem = $skipped + $itemsPerPage;
      
      $filter=$request->input('filter');
      $datemin=$filter['datemin'];
      $datemax=$filter['datemax'];
      $datemin=app('App\Http\Controllers\Divers\generale')->ReglageDateMinDateMax($datemin,'datemin');
      $datemax=app('App\Http\Controllers\Divers\generale')->ReglageDateMinDateMax($datemax,'datemax');
      $searchFilterText=$filter['searchFilterText'];
      
      $statut=$filter['statut'];
      
      if (in_array("accepted", $statut)){
        array_push($statut,'refused');
      }
      
      
      $UserID=JWTAuth::user()->id;
      $me=app('App\Http\Controllers\Divers\generale')->getUserPosts($UserID);
      $me=$me->original; $me=$me[0];
      $posts=$me->posts;
      
      $isAdmin=app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts,['Admin']);
      $isMethode=app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts,['Methode']);
      $OthersAuthorized=['ResponsableMagasin'];
      $isOthersAuthorized=app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts,$OthersAuthorized);
      
      //$isBso=in_array("bso",$type);


      // Mazilt ma da55altich les access admin methode ... 
      $first  = prev_bsm::
        whereDate('prev_bsms.created_at','>=',$datemin)
      ->whereDate('prev_bsms.created_at','<=',$datemax)
      ->WhereIn('prev_bsms.statut',$statut)

      ->join('users', function ($join) {
         $join->on('users.id', '=', 'prev_bsms.user_id');
      })
      ->join('prev_otps', function ($join){
        $join->on('prev_otps.OtpID', '=', 'prev_bsms.otp_id');
      })
      ->join('prev_interventions', function ($join) {
        $join->on('prev_interventions.InterventionID', '=', 'prev_otps.intervention_id');
      })
      ->join('equi_equipements', function ($join) use ($nodes){
        if(count($nodes)==0){
          $join->on('equi_equipements.EquipementID', '=', 'prev_interventions.equipement_id');
        }else{
          $join->on('equi_equipements.EquipementID', '=', 'prev_interventions.equipement_id')
          ->whereIn('equi_equipements.EquipementID', $nodes);
        }
      })
      ->Where(function ($query) use($searchFilterText){
         return $query   
               ->Where('users.name','like','%'.$searchFilterText.'%')
               ->orWhere('equi_equipements.equipement','like','%'.$searchFilterText.'%');
      })
      ->select('prev_bsms.BsmID','prev_bsms.otp_id as ot_id','prev_bsms.user_id','prev_bsms.statut','prev_bsms.exist','prev_bsms.created_at','prev_bsms.updated_at',
               'users.name','equi_equipements.equipement')
      ->addSelect(DB::raw("'0' as isCorrectif"));


      // Mazilt ma da55altich les access admin methode ... 
      $bsms = di_bsm::
        whereDate('di_bsms.created_at','>=',$datemin)
      ->whereDate('di_bsms.created_at','<=',$datemax)
      ->WhereIn('di_bsms.statut',$statut)

      ->join('users', function ($join) {
         $join->on('users.id', '=', 'di_bsms.user_id');
      })
      ->join('di_ots', function ($join){
        $join->on('di_ots.OtID', '=', 'di_bsms.ot_id');
      })
      ->join('di_dis', function ($join) {
        $join->on('di_dis.DiID', '=', 'di_ots.di_id');
      })
      ->join('equi_equipements', function ($join) use ($nodes){
        if(count($nodes)==0){
          $join->on('equi_equipements.EquipementID', '=', 'di_dis.equipement_id');
        }else{
          $join->on('equi_equipements.EquipementID', '=', 'di_dis.equipement_id')
          ->whereIn('equi_equipements.EquipementID', $nodes);
        }
      })
      ->Where(function ($query) use($searchFilterText){
         return $query   
               ->Where('users.name','like','%'.$searchFilterText.'%')
               ->orWhere('equi_equipements.equipement','like','%'.$searchFilterText.'%');
      })
      ->select('di_bsms.*','users.name','equi_equipements.equipement')
      ->addSelect(DB::raw("'1' as isCorrectif"))
      ->union($first);

      $bsms->orderBy('created_at', 'desc');

      $countQuery = $bsms->count();
      $bsms = $bsms->skip($skipped)->take($itemsPerPage)->get();
      return response()->json(['bsms'=>$bsms,'me'=>$me,'total'=>$countQuery]);
  
    }


    public function GetEquipementIDByOtID($OtID){
      $di_ot=di_ot::where('OtID','=',$OtID)->first();
      $DiID=$di_ot->di_id;
      $di_di=di_di::where('DiID','=',$DiID)->first();
      $equipement_id=$di_di->equipement_id;
      return $equipement_id;
    }

    public function GetEquipementIDByOtpID($OtpID){
      $di_otp=prev_otp::where('OtpID','=',$OtpID)->first();
      $InterventionID=$di_otp->intervention_id;
      $prev_intervention=prev_intervention::where('InterventionID','=',$InterventionID)->first();
      $equipement_id=$prev_intervention->equipement_id;
      return $equipement_id;
    }


    public function GetRecepteurIDByBsmID($BsmID){
      $di_bsm=di_bsm::where('BsmID','=',$BsmID)->first();
      $OtID=$di_bsm->ot_id;
      $di_ot=di_ot::where('OtID','=',$OtID)->first();
      $DiID=$di_ot->di_id;
      $di_di=di_di::where('DiID','=',$DiID)->first();
      //$equipement_id=$di_di->equipement_id;
      $recepteur_user_id=$di_di->recepteur_user_id;
      return $recepteur_user_id;
    }



    public function acceptBsm(Request $request,$id){

      $Src=$request->Src;

      if($Src=='correctif'){
      $success=true;
      $isRefused=$request->input('isRefused');
      $Reason=$request->input('Reason');
      $array=$request->input('array');
      
      $UserID=JWTAuth::user()->id;

    
      if($isRefused){
        
        $di_bsm=di_bsm::where('BsmID','=',$id)->update(['statut'=>'refused']);
        if(!$di_bsm){$success=false;}
        $di_response_bsm=di_response_bsm::create(['bsm_id'=>$id,'user_id'=>$UserID,'reason'=>$Reason]);
        if(!$di_response_bsm){$success=false;}
        return response()->json($success);
      }
      
      if(!$isRefused){
        
        $di_bsm=di_bsm::where('BsmID','=',$id)->update(['statut'=>'accepted']);
        if(!$di_bsm){$success=false;}
        $di_response_bsm=di_response_bsm::create(['bsm_id'=>$id,'user_id'=>$UserID,'reason'=>$Reason]);
        if(!$di_response_bsm){$success=false;}
        $ResponseBsmID=$di_response_bsm->ResponseBsmID;
        
        foreach($array as $article){
          
          $BsmDetID=$article['BsmDetID']; // 777 
          $qte=$article['qte'];
          
          $di_response_bsm_det=di_response_bsm_det::create([
              'response_bsm_id'=>$ResponseBsmID,
              'bsm_det_id'=>$BsmDetID,//777
              'qte'=>$qte
          ]);

          $di_response_bsm_det=di_bsm_det::where('BsmDetID','=',$BsmDetID)->first();
          $article_id=$di_response_bsm_det->article_id;
          app('App\Http\Controllers\Divers\generale')->UpdateStockByArticleID($article_id,(-1*$qte));

          if(!$di_response_bsm_det){$success=false;}

        }

        return response()->json($success);

      }

      }

      
      if($Src=='preventif'){


        $success=true;
        $isRefused=$request->input('isRefused');
        $Reason=$request->input('Reason');
        $array=$request->input('array');
        
        $UserID=JWTAuth::user()->id;
  
      
        if($isRefused){
          
          $di_bsm=prev_bsm::where('BsmID','=',$id)->update(['statut'=>'refused']);
          if(!$di_bsm){$success=false;}
          $di_response_bsm=prev_response_bsm::create(['bsm_id'=>$id,'user_id'=>$UserID,'reason'=>$Reason]);
          if(!$di_response_bsm){$success=false;}
          return response()->json($success);
        }
        
        if(!$isRefused){
          
          $di_bsm=prev_bsm::where('BsmID','=',$id)->update(['statut'=>'accepted']);
          if(!$di_bsm){$success=false;}
          $di_response_bsm=prev_response_bsm::create(['bsm_id'=>$id,'user_id'=>$UserID,'reason'=>$Reason]);
          if(!$di_response_bsm){$success=false;}
          $ResponseBsmID=$di_response_bsm->ResponseBsmID;
          
          foreach($array as $article){
            
            $BsmDetID=$article['BsmDetID']; // 777 
            $qte=$article['qte'];
            
            $di_response_bsm_det=prev_response_bsm_det::create([
                'response_bsm_id'=>$ResponseBsmID,
                'bsm_det_id'=>$BsmDetID,//777
                'qte'=>$qte
            ]);
  
            $di_response_bsm_det=prev_bsm_det::where('BsmDetID','=',$BsmDetID)->first();
            $article_id=$di_response_bsm_det->article_id;
            app('App\Http\Controllers\Divers\generale')->UpdateStockByArticleID($article_id,(-1*$qte));
            app('App\Http\Controllers\Divers\generale')->DeleteReservedArticle($article_id,$id);
            
            if(!$di_response_bsm_det){$success=false;}
  
          }
  
          return response()->json($success);
  
        }
  
        }



    }

    
    public function acceptBso(Request $request,$id){
      
      $success=true;
      $message=$request->input('message');
      $array=$request->input('array');
      
        $UserID=JWTAuth::user()->id;
        
        $di_bso=di_bso::where('BsoID','=',$id)->update(['statut'=>'repondu']);
        if(!$di_bso){$success=false;}
        $di_response_bso=di_response_bso::create(['bso_id'=>$id,'user_id'=>$UserID,'message'=>$message]);
        if(!$di_response_bso){$success=false;}
        $ResponseBsoID=$di_response_bso->ResponseBsoID;
        
        foreach($array as $article){
          
          $BsoDetID=$article['BsoDetID']; // 777 
          $bool=$article['bool'];

          
          $di_response_bso_det=di_response_bso_det::create([
              'response_bso_id'=>$ResponseBsoID, 
              'bso_det_id'=>$BsoDetID,//777
              'reponse'=>$bool
          ]);
          if(!$di_response_bso_det){$success=false;}

          if($di_response_bso_det){

            if($bool){

            $date_utilisation=Carbon::now();
            di_bso_det::where('BsoDetID','=',$BsoDetID)->update(['statut'=>'enUtilisation','date_utilisation'=>$date_utilisation]);
            $OutilID=di_bso_det::where('BsoDetID','=',$BsoDetID)->first();
            $OutilID=$OutilID->outil_id;
            art_outil::where('OutilID','=',$OutilID)->update(['reserve'=>1]);
            }else{
              di_bso_det::where('BsoDetID','=',$BsoDetID)->update(['statut'=>'refuse']);
            }
          }

        }

        $this->LoagiqueTestSiTTEstTermine($id);

        return response()->json($success);

    }


    public function termineUtilisation($id){
      $success=true;
      $date_termination=Carbon::now();
      $di_bso_det=di_bso_det::where('BsoDetID','=',$id)->update(['statut'=>'termine','date_termination'=>$date_termination]);
      if(!$di_bso_det){$success=false;}

      $OutilID=di_bso_det::where('BsoDetID','=',$id)->first();
      $OutilID=$OutilID->outil_id;
      art_outil::where('OutilID','=',$OutilID)->update(['reserve'=>0]);

      $di_bso_det_2=di_bso_det::where('BsoDetID','=',$id)->first();
      $BsoID=$di_bso_det_2->bso_id;
      $this->LoagiqueTestSiTTEstTermine($BsoID);

      return response()->json($success);



    }

    public function terminerTous($id){
      $success=true;

      $di_bso_dets=di_bso_det::where('bso_id','=',$id)->whereIn('statut',['enUtilisation'])->get();
      foreach($di_bso_dets as $ligne){
        $OutilID=di_bso_det::where('BsoDetID','=',$ligne->BsoDetID)->first();
        $OutilID=$OutilID->outil_id;
        art_outil::where('OutilID','=',$OutilID)->update(['reserve'=>0]);
      }

      $date_termination=Carbon::now();
      $di_bso_det=di_bso_det::where('bso_id','=',$id)->update(['statut'=>'termine','date_termination'=>$date_termination]);
      if(!$di_bso_det){$success=false;}
      $this->LoagiqueTestSiTTEstTermine($id);
      
    }

    public function LoagiqueTestSiTTEstTermine($BsoID){

      $di_bso_dets=di_bso_det::where('bso_id','=',$BsoID)->whereIn('statut',['enAttente','enUtilisation'])->get();
      if( count($di_bso_dets)==0 ){
         di_bso::where('BsoID','=',$BsoID)->update(['statut'=>'ferme']);

         // if tebe3 use nrod el use ferme
         $di_bso=di_bso::where('BsoID','=',$BsoID)->first();
         $OtID=$di_bso->ot_id;
         $date_cloture=Carbon::now();
         if(!$OtID){ art_2_use::where('bso_id','=',$BsoID)->update(['isOpened'=>0,'date_cloture'=>$date_cloture]); }

      }

    }

    public function getUse($id){

     $intervenants=intervenant::where('exist','=',1)->get();
     if($id==0){
       return response()->json(['intervenants'=>$intervenants]);
     }
     if($id>0){
      $art_2_use=art_2_use::where('UseID','=',$id)->first();

      $EquipementID=$art_2_use->equipement_id;
      $equipement=app('App\Http\Controllers\Equipement\equipement')->getEquipement($EquipementID);
      $equipement = $equipement->original;

      $niveaus=app('App\Http\Controllers\Equipement\equipement')->getNiveausCompletDetByEquipementID($EquipementID);
      $niveaus=$niveaus->original;

      return response()->json(['use'=>$art_2_use,'intervenants'=>$intervenants,'equipement'=>$equipement,'niveaus'=>$niveaus]);
     }

    }

    
    public function getUse2($id){
        
      $art_2_use=art_2_use::where('UseID','=',$id)->first();
      $OtID=$art_2_use->bso_id;
      
      $req3 = new Request(); // ma néj7étch el 3amaliya ma na3rach 3léch ama pas grave hawka salla7tha fi wost el fonction getBsoWithResponse b else méch perv wméch corr = null 
      $req3->merge(['Src' => 'correctif']); 
      
      $res=app('App\Http\Controllers\Correctif\correctif')->getBsoWithResponse($req3,$OtID);
      $res=$res->original;
      
      $use=$art_2_use;

      $bso=$res['bso']; 
      $response_bso=$res['response_bso']; 
      $bso_dets=$res['bso_dets']; 
      $response_bso_dets=$res['response_bso_dets']; 
      $combined=$res['combined']; 
      $bsoWithNullOtID=$res['bsoWithNullOtID']; 

      return response()->json([
        'use'=>$use,
        'bso'=>$bso,
        'response_bso'=>$response_bso,
        'bso_dets'=>$bso_dets,
        'response_bso_dets'=>$response_bso_dets,
        'combined'=>$combined,
        'bsoWithNullOtID'=>$bsoWithNullOtID
      ]);


      
 
     }


    
    public function addUse(Request $request){

      $success=true;
      $UseForm=$request->UseForm;
      
      $bso=app('App\Http\Controllers\Correctif\correctif')->addBso($request,null);
      $bso=$bso->original;
      $BsoID=$bso['bso']->BsoID;

      $UseForm['bso_id']=$BsoID;
      $UserID=JWTAuth::user()->id;
      $UseForm['user_id']=$UserID;
  
      $date=$this->DateFormYMD($UseForm['date']);
      $time=$this->TimeFormHM($UseForm['time']);
      $UseForm['date']=$date;
      $UseForm['time']=$time;
      $date1 = new DateTime($date); $time1 = new DateTime($time);
      $datetime = new DateTime($date1->format('Y-m-d') .' ' .$time1->format('H:i'));
      $UseForm['datetime']=$datetime;

      $art_2_use=art_2_use::create($UseForm);


      // accept Auto 
      $di_bso_dets=di_bso_det::where('bso_id','=',$BsoID)->get(); 
      $array=Array();
      foreach($di_bso_dets as $article) {
        $BsoDetID=$article['BsoDetID'];
        //$bool=$article['bool'];
        //$obj = new \stdClass();
        //$obj->BsoDetID=$BsoDetID;
        ////$obj->bool=$bool;
        array_push($array,['BsoDetID'=>$BsoDetID,'bool'=>true]);
      }
      $req = new Request();
      $req->merge(['message' => '']); 
      $req->merge(['array' =>$array]); 
      $this->acceptBso($req,$BsoID);
      //############

      return response()->json(['success'=>$success,'UseID'=>$art_2_use->UseID , ]);

    
    }


    
    public function editUse(Request $request,$id){

      $UseForm=$request->UseForm;
      $BsoID=$UseForm['bso_id'];
      
      $bso=app('App\Http\Controllers\Correctif\correctif')->editBso($request,$BsoID);
      $bso=$bso->original;
      
      $date=$this->DateFormYMD($UseForm['date']);
      $time=$this->TimeFormHM($UseForm['time']);
      $UseForm['date']=$date;
      $UseForm['time']=$time;
      $date1 = new DateTime($date); $time1 = new DateTime($time);
      $datetime = new DateTime($date1->format('Y-m-d') .' ' .$time1->format('H:i'));
      $UseForm['datetime']=$datetime;
      
      $art_2_use=art_2_use::where('UseID','=',$id)->update($UseForm);
      $updated_at=Carbon::now();
      $art_2_use=art_2_use::where('UseID','=',$id)->update(['updated_at'=>$updated_at]);
      art_2_use::where('UseID','=',$id)->increment('NbDeModif',1); 
      

      // changement necessaire du changement des outils
      
      $ResponseBsoID=di_response_bso::where('bso_id','=',$BsoID)->first();
      $ResponseBsoID=$ResponseBsoID->ResponseBsoID;
      
      $new_arr=$bso['new_arr'];
      $deleted_arr=$bso['deleted_arr'];

      foreach($new_arr as $article){
        $BsoDetID=$article->BsoDetID;
        $bool=true;
        $di_response_bso_det=di_response_bso_det::create([
          'response_bso_id'=>$ResponseBsoID, 
          'bso_det_id'=>$BsoDetID,
          'reponse'=>$bool
      ]);
      if(!$di_response_bso_det){$success=false;}
      if($di_response_bso_det){

        $date_utilisation=Carbon::now();
        di_bso_det::where('BsoDetID','=',$BsoDetID)->update(['statut'=>'enUtilisation','date_utilisation'=>$date_utilisation]);
        $OutilID=di_bso_det::where('BsoDetID','=',$BsoDetID)->first();
        $OutilID=$OutilID->outil_id;
        art_outil::where('OutilID','=',$OutilID)->update(['reserve'=>1]);
      }
      }
      
      foreach($deleted_arr as $bso_det){
        
        $OutilID=$bso_det->outil_id;
        $BsoDetID=$bso_det->bso_det_id;
        // di_response_bso_det::where('bso_det_id','=',$BsoDetID)->delete(); // tfassa5 deja fil editBso 
        art_outil::where('OutilID','=',$OutilID)->update(['reserve'=>0]);


        
      }

      $this->LoagiqueTestSiTTEstTermine($BsoID);


      //if(count($new_arr)>0 || count($deleted_arr)>0){
        //$art_2_use=art_2_use::where('UseID','=',$id)->update(['updated_at'=>null]);
        //$updated_at=Carbon::now();
        //$art_2_use=art_2_use::where('UseID','=',$id)->update(['updated_at'=>$updated_at]);
      //}
      
      //###############################################
      
      return response()->json($bso);

    }

 



    public function getRetour(Request $request,$id){

       $Src=$request->Src;
       if($Src=='correctif'){
       $di_bon_retour=di_bon_retour::where('RetourID','=',$id)
       ->join('users', function ($join) {
        $join->on('users.id', '=', 'di_bon_retours.user_id');
       })
       ->select('di_bon_retours.*','users.name')
       ->first();
       
       $di_bon_retour_dets=di_bon_retour_det::where('retour_id','=',$id)
       ->join('articles', function ($join) {
        $join->on('articles.ArticleID', '=', 'di_bon_retour_dets.article_id');
       })
       ->select('di_bon_retour_dets.*','articles.des')
       ->get();

       return response()->json(['retour'=>$di_bon_retour,'retour_det'=>$di_bon_retour_dets,'isCorrectif'=>true]);
       }
       if($Src=='preventif'){
        $di_bon_retour=prev_bon_retour::where('RetourID','=',$id)
        ->join('users', function ($join) {
          $join->on('users.id', '=', 'prev_bon_retours.user_id');
         })
         ->select('prev_bon_retours.*','users.name')
        ->first();
        $di_bon_retour_dets=prev_bon_retour_det::where('retour_id','=',$id)
        ->join('articles', function ($join) {
          $join->on('articles.ArticleID', '=', 'prev_bon_retour_dets.article_id');
         })
         ->select('prev_bon_retour_dets.*','articles.des')
         ->get();

        return response()->json(['retour'=>$di_bon_retour,'retour_det'=>$di_bon_retour_dets,'isCorrectif'=>false]);
        }
    }
    
    public function addRetour(Request $request,$id){
      // lazim na3mil verification esque retour tfassa5 walla 
      // walla za3ma zayid 5ater kén tfassa5 méch béch ysir chay deja !? 
      $Src=$request->Src;
      
      if($Src=='correctif'){
      $retours=$request->retours;
      $UserID=JWTAuth::user()->id;

      $di_bon_retour=di_bon_retour::where('RetourID','=',$id)->first();
      $statut=$di_bon_retour->statut;

      if($statut=='enAttente'){
      foreach($retours as $retour){
        $article_id=$retour['article_id'];
        $qter=$retour['qter'];

        di_bon_retour_det::where('retour_id','=',$id)
                          ->where('article_id','=',$article_id)->update(['qter'=>$qter]);
        $di_bon_retour_det2=di_bon_retour_det::where('retour_id','=',$id)
                          ->where('article_id','=',$article_id)->first();
        $qtear=$di_bon_retour_det2->qtear;
        $perdu=$qtear-$qter;

        app('App\Http\Controllers\Divers\generale')->UpdateStockByArticleID($article_id,$qtear);

        if($perdu>0){
          // tableau perdu generale :: create

          $retour_perdu=app('App\Http\Controllers\Divers\generale')->ArticlePerdu($article_id,$perdu);

          di_bon_retour_hist::create([
            'retour_id'=>$id,
            'article_id'=>$article_id,
            'user_id'=>$UserID,
            'type'=>'p',
            'value'=>$perdu
          ]);
        }
      }
      di_bon_retour::where('RetourID','=',$id)->update(['statut'=>'ferme']);

    }else{

      foreach($retours as $retour){
        $article_id=$retour['article_id'];
        $qter=$retour['qter'];

        $di_bon_retour_det=di_bon_retour_det::where('retour_id','=',$id)
                          ->where('article_id','=',$article_id)->first();
        $qter_a=$di_bon_retour_det->qter;
        
        di_bon_retour_det::where('retour_id','=',$id)
                          ->where('article_id','=',$article_id)->update(['qter'=>$qter]);
        
        $poupu=$qter_a-$qter;
        if($poupu!=0){
          // tableau perdu generale :: create increment poupu
         
          if($poupu>0){
             $type='p';
             $retour_perdu=app('App\Http\Controllers\Divers\generale')->ArticlePerdu($article_id,$poupu);
          }
          if($poupu<0){
            $type='pu';
            $retour_perdu=app('App\Http\Controllers\Divers\generale')->UpdatePerduByArticleID($article_id,$poupu);
          }
          app('App\Http\Controllers\Divers\generale')->UpdateStockByArticleID($article_id,(-1*$poupu));
            di_bon_retour_hist::create([
              'retour_id'=>$id,
              'article_id'=>$article_id,
              'user_id'=>$UserID,
              'type'=>$type,
              'value'=>abs($poupu)
            ]);
      }

      }


    }
     
      return response()->json($retours);
    }


    if($Src=='preventif'){
      $retours=$request->retours;
      $UserID=JWTAuth::user()->id;

      $di_bon_retour=prev_bon_retour::where('RetourID','=',$id)->first();
      $statut=$di_bon_retour->statut;
 
      if($statut=='enAttente'){
      foreach($retours as $retour){
        $article_id=$retour['article_id'];
        $qter=$retour['qter'];

        prev_bon_retour_det::where('retour_id','=',$id)
                          ->where('article_id','=',$article_id)->update(['qter'=>$qter]);
        $di_bon_retour_det2=prev_bon_retour_det::where('retour_id','=',$id)
                          ->where('article_id','=',$article_id)->first();
        $qtear=$di_bon_retour_det2->qtear;
        $perdu=$qtear-$qter;

        app('App\Http\Controllers\Divers\generale')->UpdateStockByArticleID($article_id,$qtear);

        if($perdu>0){
          // tableau perdu generale :: create

          $retour_perdu=app('App\Http\Controllers\Divers\generale')->ArticlePerdu($article_id,$perdu);

          prev_bon_retour_hist::create([
            'retour_id'=>$id,
            'article_id'=>$article_id,
            'user_id'=>$UserID,
            'type'=>'p',
            'value'=>$perdu
          ]);
        }
      }
      prev_bon_retour::where('RetourID','=',$id)->update(['statut'=>'ferme']);

    }else{

      foreach($retours as $retour){
        $article_id=$retour['article_id'];
        $qter=$retour['qter'];

        $di_bon_retour_det=prev_bon_retour_det::where('retour_id','=',$id)
                          ->where('article_id','=',$article_id)->first();
        $qter_a=$di_bon_retour_det->qter;
        
        prev_bon_retour_det::where('retour_id','=',$id)
                          ->where('article_id','=',$article_id)->update(['qter'=>$qter]);
        
        $poupu=$qter_a-$qter;
        if($poupu!=0){
          // tableau perdu generale :: create increment poupu
         
          if($poupu>0){
             $type='p';
             $retour_perdu=app('App\Http\Controllers\Divers\generale')->ArticlePerdu($article_id,$poupu);
          }
          if($poupu<0){
            $type='pu';
            $retour_perdu=app('App\Http\Controllers\Divers\generale')->UpdatePerduByArticleID($article_id,$poupu);
          }
          app('App\Http\Controllers\Divers\generale')->UpdateStockByArticleID($article_id,(-1*$poupu));
            prev_bon_retour_hist::create([
              'retour_id'=>$id,
              'article_id'=>$article_id,
              'user_id'=>$UserID,
              'type'=>$type,
              'value'=>abs($poupu)
            ]);
      }

      }


    }
     
      return response()->json($retours);
    }


    }



    public function getRetours(Request $request){
     
    $page = $request->input('page');
    $itemsPerPage = $request->input('itemsPerPage');
    
    $skipped = ($page - 1) * $itemsPerPage;
    $endItem = $skipped + $itemsPerPage;

    $filter = $request->input('filter');
    $datemin = $filter['datemin'];
    $datemax = $filter['datemax'];
    $datemin = app('App\Http\Controllers\Divers\generale')->ReglageDateMinDateMax($datemin, 'datemin');
    $datemax = app('App\Http\Controllers\Divers\generale')->ReglageDateMinDateMax($datemax, 'datemax');
    $searchFilterText = $filter['searchFilterText'];

    $statut = $filter['statut'];

    $UserID = JWTAuth::user()->id;

    /*
    $me = app('App\Http\Controllers\Divers\generale')->getUserPosts($UserID);
    $me = $me->original;
    $me = $me[0];
    $posts = $me->posts;
    
    
    $isAdmin = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, ['Admin']);
    $isMethode = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, ['Methode']);
    $OthersAuthorized = ['ChefDePoste', 'ResponsableMaintenance'];
    $isOthersAuthorized = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, $OthersAuthorized);
*/
    



$first = prev_bon_retour::whereDate('prev_bon_retours.created_at', '>=', $datemin)
      ->whereDate('prev_bon_retours.created_at', '<=', $datemax)
      ->whereIn('prev_bon_retours.statut', $statut)
      // béch ncherci b esm el article mte3 retour

      ->join('prev_bon_retour_dets', function ($join) {
        $join->on('prev_bon_retour_dets.retour_id', '=', 'prev_bon_retours.RetourID');
      })
      ->join('articles', function ($join) {
        $join->on('articles.ArticleID', '=', 'prev_bon_retour_dets.article_id');
      })
      ->join('users', function ($join) {
        $join->on('users.id', '=', 'prev_bon_retours.user_id');
      })
      ->Where(function ($query) use ($searchFilterText) {
        return $query
          ->where('users.name', 'like', '%' . $searchFilterText . '%')
          //->Where('intervenants.name', 'like', '%' . $searchFilterText . '%')
          ->Where('articles.des', 'like', '%' . $searchFilterText . '%');
      })
      //->distinct('prev_bon_retours.RetourID')
      ->select('prev_bon_retours.RetourID','prev_bon_retours.bonp_id as bon_id','prev_bon_retours.otp_id as ot_id','prev_bon_retours.user_id','prev_bon_retours.statut','prev_bon_retours.last_modif','prev_bon_retours.modifieur_user_id','prev_bon_retours.NbDeModif','prev_bon_retours.created_at','prev_bon_retours.updated_at','users.name')
      ->addSelect(DB::raw("'0' as isCorrectif"));


    $retours = di_bon_retour::whereDate('di_bon_retours.created_at', '>=', $datemin)
      ->whereDate('di_bon_retours.created_at', '<=', $datemax)
      ->whereIn('di_bon_retours.statut', $statut)
      // béch ncherci b esm el article mte3 retour
      ->join('di_bon_retour_dets', function ($join) {
        $join->on('di_bon_retour_dets.retour_id', '=', 'di_bon_retours.RetourID');
      })
      ->join('articles', function ($join) {
        $join->on('articles.ArticleID', '=', 'di_bon_retour_dets.article_id');
      })
      ->join('users', function ($join) {
        $join->on('users.id', '=', 'di_bon_retours.user_id');
      })
      ->Where(function ($query) use ($searchFilterText) {
        return $query
          ->where('users.name', 'like', '%' . $searchFilterText . '%')
          //->Where('intervenants.name', 'like', '%' . $searchFilterText . '%')
          ->Where('articles.des', 'like', '%' . $searchFilterText . '%');
      })
      ->select('di_bon_retours.*','users.name')
     // ->distinct('di_bon_retours.RetourID')
      ->addSelect(DB::raw("'1' as isCorrectif"))
      ->union($first);

    $retours->distinct('RetourID')->orderBy('created_at', 'desc');

    $countQuery = $retours->count();
    $retours = $retours->skip($skipped)->take($itemsPerPage)->get();

    return response()->json(['retours' => $retours, 'total' => $countQuery]);

    }


    public function getRetourForAff(Request $request,$id){
      
      $Src=$request->Src;
      if($Src=='correctif'){
      $di_bon_retour=di_bon_retour::where('RetourID','=',$id)
      ->join('users', function ($join) {
        $join->on('users.id', '=', 'di_bon_retours.user_id');
      })
      ->leftjoin('users as modifieur', function ($join) {
        $join->on('modifieur.id', '=', 'di_bon_retours.modifieur_user_id');
      })->select('di_bon_retours.*','modifieur.name as modifieur','users.name')->first();
      
      $di_bon_retour_dets=di_bon_retour_det::where('retour_id','=',$id)
      ->join('articles', function ($join) {
        $join->on('articles.ArticleID', '=', 'di_bon_retour_dets.article_id');
      })->select('di_bon_retour_dets.*','articles.des')->get();

      $di_bon_retour_hists=di_bon_retour_hist::where('retour_id','=',$id)
      ->join('articles', function ($join) {
        $join->on('articles.ArticleID', '=', 'di_bon_retour_hists.article_id');
      })->join('users', function ($join) {
        $join->on('users.id', '=', 'di_bon_retour_hists.user_id');
      })->select('di_bon_retour_hists.*','users.name','articles.des')->get();
      

      return response()->json(['retour'=>$di_bon_retour,'retour_det'=>$di_bon_retour_dets,'hists'=>$di_bon_retour_hists]);

      }

      if($Src=='preventif'){
        $di_bon_retour=prev_bon_retour::where('RetourID','=',$id)
        ->join('users', function ($join) {
          $join->on('users.id', '=', 'prev_bon_retours.user_id');
        })
        ->leftjoin('users as modifieur', function ($join) {
          $join->on('modifieur.id', '=', 'prev_bon_retours.modifieur_user_id');
        })->select('prev_bon_retours.*','modifieur.name as modifieur','users.name')->first();
        
        $di_bon_retour_dets=prev_bon_retour_det::where('retour_id','=',$id)
        ->join('articles', function ($join) {
          $join->on('articles.ArticleID', '=', 'prev_bon_retour_dets.article_id');
        })->select('prev_bon_retour_dets.*','articles.des')->get();
  
        $di_bon_retour_hists=prev_bon_retour_hist::where('retour_id','=',$id)
        ->join('articles', function ($join) {
          $join->on('articles.ArticleID', '=', 'prev_bon_retour_hists.article_id');
        })->join('users', function ($join) {
          $join->on('users.id', '=', 'prev_bon_retour_hists.user_id');
        })->select('prev_bon_retour_hists.*','users.name','articles.des')->get();
        
  
        return response()->json(['retour'=>$di_bon_retour,'retour_det'=>$di_bon_retour_dets,'hists'=>$di_bon_retour_hists]);
  
        }


    }



}
