<?php

namespace App\Http\Controllers\Correctif;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Model\Preventif\prev_response_bsm;
use App\Model\Preventif\prev_response_bsm_det;

/*
use App\Model\Preventif\prev_bso;
use App\Model\Preventif\prev_bso_det;
*/

use App\Model\Correctif\di_di;
use App\Model\Correctif\di_ot;
use App\Model\Correctif\di_ot_intervenant;
use App\Model\Correctif\di_bsm;
use App\Model\Preventif\prev_bsm;
use App\Model\Correctif\di_bsm_det;
use App\Model\Preventif\prev_bsm_det;
use App\Model\Correctif\di_response_bsm;
use App\Model\Correctif\di_response_bsm_det;

use App\Model\Correctif\di_bso;
use App\Model\Correctif\di_bso_det;
use App\Model\Correctif\di_response_bso;
use App\Model\Correctif\di_response_bso_det;

use App\Model\Correctif\di_di_plan;

use App\Model\Magasin\art_outil;

use App\Model\Correctif\di_bon;
use App\Model\Correctif\di_bon_intervenant;
use App\Model\Correctif\di_bon_urp;
use App\Model\Correctif\di_bon_retour;
use App\Model\Correctif\di_bon_retour_det;
use App\Model\Correctif\di_bon_retour_hist;

use App\Model\Magasin\art_2_use;





use Illuminate\Support\Facades\DB;

use App\Model\Divers\error;

use App\User;
use Carbon\Carbon;
use DateTime;
use Dotenv\Regex\Success;
use JWTFactory;
use JWTAuth;

use App\Notifications\NewAutoDiExecuted;



class correctif extends Controller
{

  public function DateFormYMD($date)
  {
    $date = Carbon::parse($date);
    $date = $date->format('Y-m-d');
    return $date;
  }
  public function TimeFormHM($time)
  {
    $time = Carbon::parse($time);
    $time = $time->format('H:i');
    return $time;
  }


  public function  addDi(Request $request)
  {
    $success=true;
    $msg="La demande d'intervention est ajoutée avec succées .";
    
    $form = $request->input('form');
    unset($form['created_at']);
    unset($form['updated_at']);
    
    $UserID = JWTAuth::user()->id;
    $form['user_id'] = $UserID;

    $date = $this->DateFormYMD($form['date']);
    $time = $this->TimeFormHM($form['time']);
    $form['date'] = $date;
    $form['time'] = $time;

    $date1 = new DateTime($date);
    $time1 = new DateTime($time);
    $datetime = new DateTime($date1->format('Y-m-d') . ' ' . $time1->format('H:i'));
    $form['datetime'] = $datetime;

    $form['statut'] = "enAttente";

    $di_di = di_di::create($form);

    if(!$di_di){
      $success=false;
      $msg="Error .";
      $di_di=null;
    }
    
    return response()->json(['success'=>$success,'di'=>$di_di,'msg'=>$msg]);


  }


  public function deleteDi($id){

    $success=true;
    $msg="Di est supprimé avec succées .";

    $di_di=di_di::where('DiID','=',$id)->where('exist','=',true)->first();
    if(!$di_di){
      $success=false; $msg="Error .";
      return response()->json(['success'=>$success,'result'=>false,'msg'=>$msg]);
    }
    $statut=$di_di->statut;
    if($statut=='planifie'){
      $di_di_plan=di_di_plan::where('di_id', '=', $id)->first();
      $PlanID=$di_di_plan->PlanID;
      $isExecuted=$di_di_plan->isExecuted;
      if(!$isExecuted){
        $this->deplanifierDi($PlanID);
      }else{
        $success=false;
        $msg="Error .";
      }
    }else if($statut!='enAttente'){
      $success=false;
      $msg="Error .";
    }
  
     $di_di_u=false;
     if($success){ 
      $di_di_u=di_di::where('DiID','=',$id)->where('exist','=',true)->update(['exist'=>false]);
     }

    if($di_di_u){
      return response()->json(['success'=>$success,'result'=>$di_di_u,'msg'=>$msg]);
    }else{
     return response()->json(['success'=>false,'result'=>$di_di_u,'msg'=>$msg]);
    }

  }



  public function  editDi(Request $request, $id)
  {
    $success=true;
    $msg="Le Demande d'intervention est Modifiée avec succès.";
    $unsetEquipementID=false;

    $form = $request->input('form');
    unset($form['created_at']);
    unset($form['updated_at']);
    unset($form['statut']);

    $DiID = $request->input('DiID');


    // test logique
    $UserID = JWTAuth::user()->id;
    $me = app('App\Http\Controllers\Divers\generale')->getUserPosts($UserID);
    $me = $me->original;
    $me = $me[0];
    $posts = $me->posts;
    $isAdmin = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, ['Admin']);
    $isMethode = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, ['Methode']);
    $test_di_di = di_di::where('DiID', '=', $id)->first();
    $user_id = $test_di_di->demandeur_user_id;
    $exist = $test_di_di->exist;
    $statut=$test_di_di->statut;
    $equipement_id=$test_di_di->equipement_id;

    
    
    if ($isAdmin || $isMethode) { /* rien a faire */
    } else {
      if ($user_id != $UserID) {
        //return response()->json(false);
        $success=false; $msg="Error";
        return response()->json(['success'=>$success,'di'=>false,'msg'=>$msg]);
      }
    }
    if (!$exist) {
      $success=false; $msg="Error";
      return response()->json(['success'=>$success,'di'=>false,'msg'=>$msg]);
    }
    if ($statut=='enCours' || $statut=='ferme') {
      if($equipement_id!=$form['equipement_id']){
      unset($form['equipement_id']);
      $unsetEquipementID=true;
      }
    }else if($statut=='planifie'){
        $di_di_plan=di_di_plan::where('di_id', '=', $id)->first();
        //$PlanID=$di_di_plan->PlanID;
        $isExecuted=$di_di_plan->isExecuted;
        if($isExecuted){
          if($equipement_id!=$form['equipement_id']){
          unset($form['equipement_id']);
          $unsetEquipementID=true;
          }
        }
    }

    $date = $this->DateFormYMD($form['date']);
    $time = $this->TimeFormHM($form['time']);
    $form['date'] = $date;
    $form['time'] = $time;

    $date1 = new DateTime($date);
    $time1 = new DateTime($time);
    $datetime = new DateTime($date1->format('Y-m-d') . ' ' . $time1->format('H:i'));
    $form['datetime'] = $datetime;

    //$form['statut']="enAttente";

    $di_di = di_di::where('DiID', '=', $id)->update($form);

    //return response()->json($di_di);
    if($unsetEquipementID){
      $msg.= " L'equipement ne pas etre modifiée aprés l'execution du di .";
    }
    
    return response()->json(['success'=>$success,'di'=>$di_di,'msg'=>$msg]);
     
  }

  public function getDi($id)
  {

    $di_di = di_di::where('DiID', '=', $id)
    ->join('equi_anomalies', function ($join) {
      $join->on('equi_anomalies.AnomalieID', '=', 'di_dis.anomalie_id');
    })
    ->leftjoin('di_di_plans', function ($join) {
      $join->on('di_di_plans.di_id', '=', 'di_dis.DiID');
    })
    ->select('di_dis.*','equi_anomalies.anomalie','di_di_plans.PlanID')
    ->first();

    $plan=null;
    if($di_di->PlanID){
      $plan=di_di_plan::where('PlanID','=',$di_di->PlanID)
      ->join('users', function ($join) {
        $join->on('users.id', '=', 'di_di_plans.user_id');
      })
      ->leftjoin('users as modifieur', function ($join) {
        $join->on('modifieur.id', '=', 'di_di_plans.modifieur_user_id');
      })
      ->select('di_di_plans.*','modifieur.name as modifieur','users.name')
      ->first();
    }



    $EquipementID0 = $di_di->equipement_id;
    $niveaux = app('App\Http\Controllers\Equipement\equipement')->getNiveausCompletDetByEquipementID($EquipementID0);
    $niveaux = $niveaux->original;


    $EquipementID = $di_di->equipement_id;
    $equipement = app('App\Http\Controllers\Equipement\equipement')->getEquipement($EquipementID);

    $di_di2 = di_di::where('DiID', '=', $id)
      ->join('equi_equipements', function ($join) {
        $join->on('equi_equipements.EquipementID', '=', 'di_dis.equipement_id');
      })
      ->select('di_dis.*', 'equi_equipements.equipement', 'equi_equipements.Niv')
      ->first();

    $equipement = $equipement->original;

    $demandeur = app('App\Http\Controllers\Divers\generale')->getUserPosts($di_di->demandeur_user_id);
    $demandeur = $demandeur->original;
    $demandeur = $demandeur[0];

    $recepteur = app('App\Http\Controllers\Divers\generale')->getUserPosts($di_di->recepteur_user_id);
    $recepteur = $recepteur->original;
    $recepteur = $recepteur[0];
    //$posts=$demandeur->posts;


    //$niveaus=app('App\Http\Controllers\Equipement\equipement')->getNiveauDetByEquipementID($EquipementID);
    $niveaus = app('App\Http\Controllers\Equipement\equipement')->getNiveausCompletDetByEquipementID($EquipementID);
    $niveaus = $niveaus->original;

    $taches = app('App\Http\Controllers\Equipement\equipement')->getTachesByEquipementID($EquipementID);
    $taches = $taches->original;

    return response()->json(['di' => $di_di, 'di2' => $di_di2, 'equipement' => $equipement, 'demandeur' => $demandeur,'recepteur'=>$recepteur, 'niveaus' => $niveaus,'niveaux'=>$niveaux, 'taches' => $taches,'plan'=>$plan]);
  }

  public function getDiForAffichage($id)
  {

    $UserID = JWTAuth::user()->id;
    $me = app('App\Http\Controllers\Divers\generale')->getUserPosts($UserID);
    $me = $me->original;
    $me = $me[0];
    $posts = $me->posts;
    $isAdmin = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, ['Admin']);
    $isMethode = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, ['Methode']);

    $di_di = di_di::where('DiID', '=', $id)
      ->join('users', function ($join) {
        $join->on('users.id', '=', 'di_dis.user_id');
      })
      ->join('users as demandeur', function ($join) {
        $join->on('demandeur.id', '=', 'di_dis.demandeur_user_id');
      })
      ->join('users as recepteur', function ($join) {
        $join->on('recepteur.id', '=', 'di_dis.recepteur_user_id');
      })
      ->join('equi_equipements', function ($join) {
        $join->on('equi_equipements.EquipementID', '=', 'di_dis.equipement_id');
      })
      ->join('equi_anomalies', function ($join) {
        $join->on('equi_anomalies.AnomalieID', '=', 'di_dis.anomalie_id');
      })
      ->leftjoin('di_di_plans', function ($join) {
        $join->on('di_di_plans.di_id', '=', 'di_dis.DiID');
      })
      ->Where(function ($query) use ($isAdmin, $isMethode, $UserID) {
        if (($isAdmin || $isMethode)) {
          return $query;
        } else {
          return $query->where('di_dis.recepteur_user_id', '=', $UserID)
            ->orWhere('di_dis.demandeur_user_id', '=', $UserID);
        }
      })

      ->select('di_dis.*', 'users.name', 'recepteur.name as recepteur', 'demandeur.name as demandeur', 'equi_equipements.equipement', 'equi_anomalies.anomalie', 'di_di_plans.PlanID')
      ->orderBy('di_dis.created_at', 'desc')
      ->first();
      
      $plan=null;
      if($di_di->PlanID){
        $plan=di_di_plan::where('PlanID','=',$di_di->PlanID)
        ->join('users', function ($join) {
          $join->on('users.id', '=', 'di_di_plans.user_id');
        })
        ->leftjoin('users as modifieur', function ($join) {
          $join->on('modifieur.id', '=', 'di_di_plans.modifieur_user_id');
        })
        ->select('di_di_plans.*','modifieur.name as modifieur','users.name')
        ->first();

      }

      $EquipementID = $di_di->equipement_id;
      $niveaux = app('App\Http\Controllers\Equipement\equipement')->getNiveausCompletDetByEquipementID($EquipementID);
      $niveaux = $niveaux->original;

      $OtID=null;
      $statut=$di_di->statut;
      if($statut=='enCours'){
        $ot=di_ot::where('di_id','=',$id)->where('exist','=',true)->first();
        $OtID=$ot->OtID;
      }

      //return response()->json($di_di);
      return response()->json(['di'=>$di_di,'niveaux'=>$niveaux,'OtID'=>$OtID,'plan'=>$plan]);
  }

  public function getDis(Request $request)
  {

    $page = $request->input('page');
    $itemsPerPage = $request->input('itemsPerPage');
    $nodes = $request->input('nodes');

    $skipped = ($page - 1) * $itemsPerPage;
    $endItem = $skipped + $itemsPerPage;

    $filter = $request->input('filter');
    $datemin = $filter['datemin'];
    $datemax = $filter['datemax'];
    $datemin = app('App\Http\Controllers\Divers\generale')->ReglageDateMinDateMax($datemin, 'datemin');
    $datemax = app('App\Http\Controllers\Divers\generale')->ReglageDateMinDateMax($datemax, 'datemax');
    $searchFilterText = $filter['searchFilterText'];

    $type = $filter['type'];
    $degre = $filter['degre'];
    $statut = $filter['statut'];

    $UserID = JWTAuth::user()->id;
    $me = app('App\Http\Controllers\Divers\generale')->getUserPosts($UserID);
    $me = $me->original;
    $me = $me[0];
    $posts = $me->posts;

    $isAdmin = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, ['Admin']);
    $isMethode = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, ['Methode']);
    $OthersAuthorized = ['ChefDeEquipe', 'ChefDePoste', 'ResponsableMaintenance'];
    $isOthersAuthorized = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, $OthersAuthorized);

    $dis = di_di::whereDate('di_dis.datetime', '>=', $datemin)
      ->whereDate('di_dis.datetime', '<=', $datemax)
      ->where('di_dis.exist','=',1)
      ->whereIn('di_dis.type', $type)
      ->whereIn('di_dis.degre', $degre)
      ->whereIn('di_dis.statut', $statut)
      ->Where(function ($query) use ($isAdmin, $isMethode, $isOthersAuthorized, $UserID) {
        if (($isAdmin || $isMethode)) {
          return $query;
        } else if ($isOthersAuthorized) {
          return $query->where('di_dis.user_id', '=', $UserID)
            ->orWhere('demandeur_user_id', '=', $UserID)
            ->orWhere('recepteur_user_id', '=', $UserID);
        }
      })
      ->leftjoin('di_di_plans', function ($join) {
        $join->on('di_di_plans.di_id', '=', 'di_dis.DiID');
      })
      ->leftjoin('di_ots', function ($join) {
        $join->on('di_ots.di_id', '=', 'di_dis.DiID');
      })
      ->join('users as demandeur', function ($join) {
        $join->on('demandeur.id', '=', 'di_dis.demandeur_user_id');
      })
      ->join('users as recepteur', function ($join) {
        $join->on('recepteur.id', '=', 'di_dis.recepteur_user_id');
      })
      ->join('users', function ($join) {
        $join->on('users.id', '=', 'di_dis.user_id');
      })
      ->join('equi_equipements', function ($join) use ($nodes) {
        if (count($nodes) == 0) {
          $join->on('equi_equipements.EquipementID', '=', 'di_dis.equipement_id');
        } else {
          $join->on('equi_equipements.EquipementID', '=', 'di_dis.equipement_id')
            ->whereIn('equi_equipements.EquipementID', $nodes);
        }
      })
      ->join('equi_anomalies', function ($join) {
        $join->on('equi_anomalies.AnomalieID', '=', 'di_dis.anomalie_id');
      })
      ->Where(function ($query) use ($searchFilterText) {
        return $query
          ->where('equi_anomalies.anomalie', 'like', '%' . $searchFilterText . '%')
          ->orWhere('equi_equipements.equipement', 'like', '%' . $searchFilterText . '%')
          ->orWhere('recepteur.name', 'like', '%' . $searchFilterText . '%')
          ->orWhere('demandeur.name', 'like', '%' . $searchFilterText . '%')
          ->orWhere('users.name', 'like', '%' . $searchFilterText . '%');
      })
      ->select('di_dis.*', 'di_ots.OtID', 'users.name', 'recepteur.name as recepteur', 'demandeur.name as demandeur', 'equi_equipements.equipement', 'equi_anomalies.anomalie', 'di_di_plans.PlanID')
      ->orderBy('di_dis.created_at', 'desc');

    $countQuery = $dis->count();
    $dis = $dis->skip($skipped)->take($itemsPerPage)->get();

    return response()->json(['dis' => $dis, 'me' => $me, 'total' => $countQuery]);
  }



  public function getOts(Request $request)
  {

    $page = $request->input('page');
    $itemsPerPage = $request->input('itemsPerPage');
    $nodes = $request->input('nodes');

    $skipped = ($page - 1) * $itemsPerPage;
    $endItem = $skipped + $itemsPerPage;

    $filter = $request->input('filter');
    $datemin = $filter['datemin'];
    $datemax = $filter['datemax'];
    $datemin = app('App\Http\Controllers\Divers\generale')->ReglageDateMinDateMax($datemin, 'datemin');
    $datemax = app('App\Http\Controllers\Divers\generale')->ReglageDateMinDateMax($datemax, 'datemax');
    $searchFilterText = $filter['searchFilterText'];

    $type = $filter['type'];
    $degre = $filter['degre'];
    $statut = $filter['statut'];


    $UserID = JWTAuth::user()->id;
    $me = app('App\Http\Controllers\Divers\generale')->getUserPosts($UserID);
    $me = $me->original;
    $me = $me[0];
    $posts = $me->posts;

    $isAdmin = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, ['Admin']);
    $isMethode = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, ['Methode']);
    $OthersAuthorized = ['ChefDePoste', 'ResponsableMaintenance'];
    $isOthersAuthorized = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, $OthersAuthorized);

    $ots = di_ot::whereDate('di_ots.datetime', '>=', $datemin)
      ->whereDate('di_ots.datetime', '<=', $datemax)
      ->where('di_ots.exist','=',true)
      ->join('di_dis', function ($join) use ($type, $degre) {
        $join->on('di_dis.DiID', '=', 'di_ots.di_id')
          ->whereIn('di_dis.type', $type)
          ->whereIn('di_dis.degre', $degre);
      })
      //->where('di_dis.statut','=','enCours')
      ->whereIn('di_ots.statut', $statut)
      ->Where(function ($query) use ($isAdmin, $isMethode, $isOthersAuthorized, $UserID) {
        if (($isAdmin || $isMethode)) {
          return $query;
        } else if ($isOthersAuthorized) {
          return $query->where('di_dis.recepteur_user_id', '=', $UserID);
          //return $query->where('di_ots.user_id','=',$UserID);
          //->orWhere('di_dis.demandeur_user_id','=',$UserID)
          //->orWhere('di_dis.recepteur_user_id','=',$UserID);
        } else {
          // condition illogique
          return $query->where('di_dis.recepteur_user_id', '<', 0);
        }
      })
      ->leftjoin('di_di_plans', function ($join) {
        $join->on('di_di_plans.di_id', '=', 'di_dis.DiID');
      })
      ->join('users as demandeur', function ($join) {
        $join->on('demandeur.id', '=', 'di_dis.demandeur_user_id');
      })
      ->join('users as recepteur', function ($join) {
        $join->on('recepteur.id', '=', 'di_dis.recepteur_user_id');
      })
      ->join('users', function ($join) {
        $join->on('users.id', '=', 'di_dis.user_id');
      })
      ->join('equi_equipements', function ($join) use ($nodes) {
        if (count($nodes) == 0) {
          $join->on('equi_equipements.EquipementID', '=', 'di_dis.equipement_id');
        } else {
          $join->on('equi_equipements.EquipementID', '=', 'di_dis.equipement_id')
            ->whereIn('equi_equipements.EquipementID', $nodes);
        }
      })
      ->join('equi_anomalies', function ($join) {
        $join->on('equi_anomalies.AnomalieID', '=', 'di_dis.anomalie_id');
      })
      ->leftjoin('equi_taches', function ($join) {
        $join->on('equi_taches.TacheID', '=', 'di_ots.tache_id');
      })
      ->Where(function ($query) use ($searchFilterText) {
        return $query
          ->where('equi_anomalies.anomalie', 'like', '%' . $searchFilterText . '%')
          ->orWhere('equi_taches.tache', 'like', '%' . $searchFilterText . '%')
          ->orWhere('equi_equipements.equipement', 'like', '%' . $searchFilterText . '%')
          ->orWhere('recepteur.name', 'like', '%' . $searchFilterText . '%')
          ->orWhere('demandeur.name', 'like', '%' . $searchFilterText . '%')
          ->orWhere('users.name', 'like', '%' . $searchFilterText . '%');
      })
      ->select(
        'di_ots.OtID',
        'di_ots.statut as ot_statut',
        'di_ots.date as ot_date',
        'di_ots.time as ot_time',
        'di_ots.datetime as ot_datetime',
        'di_ots.created_at as ot_created_at',
        'di_ots.updated_at as ot_updated_at',
        'di_ots.date_execution',
        'di_ots.user_id',
        'di_dis.DiID',
        'di_dis.statut as di_statut',
        'di_dis.date as di_date',
        'di_dis.time as di_time',
        'di_dis.datetime as di_datetime',
        'di_dis.created_at as di_created_at',
        'di_dis.type',
        'di_dis.degre',
        'di_dis.demandeur_user_id',
        'di_dis.recepteur_user_id',
        'users.name',
        'recepteur.name as recepteur',
        'demandeur.name as demandeur',
        'equi_equipements.equipement',
        'equi_anomalies.anomalie',
        'equi_taches.tache',
        'di_di_plans.PlanID'
      )
      ->orderBy('di_ots.created_at', 'desc');



    $countQuery = $ots->count();
    $ots = $ots->skip($skipped)->take($itemsPerPage)->get();

    return response()->json(['dis' => $ots, 'me' => $me, 'total' => $countQuery]);
  }


  public function  addOt(Request $request, $isSystem)
  {

    $success=true;
    $msg="l'OT est executer avec succée .";
    $di_ot=null;

    $form = $request->input('form');
    $intervenants = $request->input('intervenants');

    // return response()->json($form); 

    // securite béch ma yajoutich akther men ot fi nafs el di 
    $form_di_id = $form['di_id'];
    $test_di_ots = di_ot::where('di_id', '=', $form_di_id)->where('exist', '=', 1)->get();

    $DI_DATA=$this->DI_DATA($form_di_id);
    //if(!$isSystem && $DI_DATA->statut=='planifie' && $DI_DATA->isExecuted){
      //deplanifierDi
    //}
    // 7asilou mazalit prob sghira hiya ki ya3mél execution manuelle w lo5ra planifee non executer
    // w 7kéyét execution manuelle automatique ??! 
    // walla tt simplment nchoufou el plan w kén ma fihéch date execution tet7at w kén isExecuted =0 twalli 1 tt simplement 

   
    if($DI_DATA->exist && !$DI_DATA->isDiExecuted ) { //&&  !($DI_DATA->PlanID &&  !$DI_DATA->isExecuted) ){

    if (count($test_di_ots) > 0) {
      //return response()->json(false);
      $success=false;
      $msg="Error . ";
    }else{

    $now = Carbon::now();
    $UserID = JWTAuth::user()->id;
    $form['user_id'] = $UserID;

    $date = $this->DateFormYMD($form['date']);
    $time = $this->TimeFormHM($form['time']);
    $form['date'] = $date;
    $form['time'] = $time;

    $date1 = new DateTime($date);
    $time1 = new DateTime($time);
    $datetime = new DateTime($date1->format('Y-m-d') . ' ' . $time1->format('H:i'));
    $form['datetime'] = $datetime;

    if ($isSystem == 0) {
      $form['statut'] = "enCours";
    } else {
      $form['statut'] = "enAttente";
      $form['date_execution'] = null;
    }



    // $form['exist']=1;

    $di_ot = di_ot::create($form);
    $OtID = $di_ot->OtID;
    $DiID = $di_ot->di_id;


    if ($isSystem == 0) {
      $update = di_ot::where('OtID', '=', $OtID)->update(['date_execution' => $di_ot->created_at, 'updated_at' => $di_ot->created_at]);
      di_di::where('DiID', '=', $DiID)->update(['statut' => 'enCours']);


      // kén 5alla page add ot ma7loula w 3mal plan  w executa mel page 
      $DI_DATA2=$this->DI_DATA($DiID);
      $PlanID2=$DI_DATA2->PlanID;
      $isExecuted2=$DI_DATA2->isExecuted;
      if($PlanID2 && !$isExecuted2){
        di_di_plan::where('di_id','=',$DiID)->update(['date_execution'=>$now,'isExecuted'=>1]);
      }


    }

    //error::insert(['error'=>$DiID.' , issystem =  '.$isSystem. ' , planID='.$PlanID2.' , isExecuted'.$isExecuted2]);






    // intervenants 
    foreach ($intervenants as $IntervenantID) {
      di_ot_intervenant::create([
        'ot_id' => $OtID,
        'intervenant_id' => $IntervenantID
      ]);
    }


    }
    //return response()->json($di_ot);
   
  }else{
    $success=false;
    $msg="Error .";
  }

    return response()->json(['success'=>$success,'ot'=>$di_ot,'msg'=>$msg]);
    
  }
  

  public function DI_DATA($DiID){

    $exist=0;
    $PlanID=0;
    $isExecuted=0;
    $statut='';
    $isDiExecuted=0;
    $OtID=0;
    $BonID=0;
    

  $di_di_test=di_di::where('DiID', '=', $DiID)->where('exist','=',true)->first();
  if($di_di_test){

    $exist=1;
    $statut=$di_di_test->statut;
    $di_di_plan = di_di_plan::where('di_id', '=', $DiID)->where('exist','=',true)->first();
    if($di_di_plan){
    $PlanID=$di_di_plan->PlanID;
    $isExecuted=$di_di_plan->isExecuted;
    }
    if($statut=='enCours' || $statut=='ferme' || ($statut=='planifie' && $isExecuted) ){
      $isDiExecuted=1;
    }
    
  }
  
  $DI_DATA=new \stdClass();
  $DI_DATA->exist=$exist;
  $DI_DATA->PlanID=$PlanID;
  $DI_DATA->isExecuted=$isExecuted;
  $DI_DATA->statut=$statut;
  $DI_DATA->isDiExecuted=$isDiExecuted;
  $DI_DATA->OtID=$OtID;
  $DI_DATA->BonID=$BonID;

  return $DI_DATA;

  }
  


  public function getOt($id)
  {

    $UserID = JWTAuth::user()->id;
    $me = app('App\Http\Controllers\Divers\generale')->getUserPosts($UserID);
    $me = $me->original;
    $me = $me[0];
    $posts = $me->posts;
    $isAdmin = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, ['Admin']);
    $isMethode = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, ['Methode']);

    $di_ot = di_ot::where('OtID', '=', $id)
    ->leftjoin('di_bons', function ($join) {
      $join->on('di_bons.ot_id', '=', 'di_ots.OtID');
    })
      ->join('users', function ($join) {
        $join->on('users.id', '=', 'di_ots.user_id');
      })
      ->leftjoin('users as modifieur', function ($join) {
        $join->on('modifieur.id', '=', 'di_ots.modifieur_user_id');
      })
      ->join('di_dis', function ($join) {
        $join->on('di_dis.DiID', '=', 'di_ots.di_id');
      })
      ->leftjoin('equi_taches', function ($join) {
        $join->on('equi_taches.TacheID', '=', 'di_ots.tache_id');
      })
      ->join('users as demandeur', function ($join) {
        $join->on('demandeur.id', '=', 'di_dis.demandeur_user_id');
      })
      ->join('users as recepteur', function ($join) {
        $join->on('recepteur.id', '=', 'di_dis.recepteur_user_id');
      })
      ->join('equi_anomalies', function ($join) {
        $join->on('equi_anomalies.AnomalieID', '=', 'di_dis.anomalie_id');
      })
      ->Where(function ($query) use ($isAdmin, $isMethode, $UserID) {
        if (($isAdmin || $isMethode)) {
          return $query;
        } else {
          return $query->where('di_dis.recepteur_user_id', '=', $UserID)
            ->orWhere('di_dis.demandeur_user_id', '=', $UserID);
        }
      })
      ->select(
        'di_ots.*',
        'users.name',
        'modifieur.name as modifieur_name',
        'equi_taches.tache',
        'equi_anomalies.anomalie',
        'recepteur.name as recepteur',
        'demandeur.name as demandeur',
        'di_dis.recepteur_user_id',
        'di_dis.demandeur_user_id',
        'di_dis.equipement_id',
        'di_bons.BonID'
      )
      ->first();

    $di_bsos = di_bso::where('ot_id', '=', $id)->where('isCorrectif','=',1)->get();
    $di_bsms = di_bsm::where('ot_id', '=', $id)->get();
    

    $intervevants = di_ot_intervenant::where('ot_id', '=', $id)
      ->select('di_ot_intervenants.intervenant_id')
      ->get();

    $di_ot_2 = di_ot::where('OtID', '=', $id)->first();
    $statut_2=$di_ot_2->statut;
    if($statut_2!='ferme'){
    $intervevants2 = di_ot_intervenant::where('ot_id', '=', $id)
      ->join('intervenants', function ($join) {
        $join->on('intervenants.IntervenantID', '=', 'di_ot_intervenants.intervenant_id');
      })
      ->select('intervenants.*')
      ->get();
    }else{
      $di_bon_2=di_bon::where('ot_id','=',$id)->first();
      $bon_id_2=$di_bon_2->BonID;
      $intervevants2 = di_bon_intervenant::where('bon_id', '=', $bon_id_2)
      ->join('intervenants', function ($join) {
        $join->on('intervenants.IntervenantID', '=', 'di_bon_intervenants.intervenant_id');
      })
      ->select('intervenants.*')
      ->get();
    }
    


    

    $intervevantsIds = array();
    foreach ($intervevants as $intervevant) {
      array_push($intervevantsIds, $intervevant['intervenant_id']);
    }

    $EquipementID = $di_ot->equipement_id;
    $niveaux = app('App\Http\Controllers\Equipement\equipement')->getNiveausCompletDetByEquipementID($EquipementID);
    $niveaux = $niveaux->original;


    return response()->json(['ot' => $di_ot, 'intervenants' => $intervevantsIds, 'intervenants2' => $intervevants2, 'bsms' => $di_bsms, 'bsos' => $di_bsos,'niveaux'=>$niveaux]);
  }


  public function OT_DATA($OtID){
    $exist=0;
    $statut='';
    
    $di_ot_test = di_ot::where('OtID', '=', $OtID)->where('exist','=',1)->first();
    if($di_ot_test){ 
      $exist=1 ;
      $statut=$di_ot_test->statut;
    }

    $OT_DATA=new \stdClass();
    $OT_DATA->exist=$exist;
    $OT_DATA->statut=$statut;

    return $OT_DATA;

  }

  public function  editOt(Request $request, $id)
  {
    
    // lazim tkoun mazélét enAttente walla enCours
    // mazalit exist
    


    $success=true;
    $msg="l'OT est modifiée avec succée .";

    
    $OT_DATA=$this->OT_DATA($id);
    if( ! ( $OT_DATA->exist && ( $OT_DATA->statut=='enAttente' ||  $OT_DATA->statut=='enCours') ) ){
      $success=false;
      $msg="Error .";
    }
   



    $UserID = JWTAuth::user()->id;
    
    $me = app('App\Http\Controllers\Divers\generale')->getUserPosts($UserID);
    $me = $me->original;
    $me = $me[0];
    $posts = $me->posts;
    $isAdmin = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, ['Admin']);
    $isMethode = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, ['Methode']);
    
    //return response()->json($isMethode);
    // test logique 

  if($success){
    $test_di_ot = di_ot::where('OtID', '=', $id)
      ->join('di_dis', function ($join) {
        $join->on('di_dis.DiID', '=', 'di_ots.di_id');
      })->select('di_dis.recepteur_user_id')->first();
    $user_id = $test_di_ot->recepteur_user_id;
    if ($isAdmin || $isMethode) {/* rien a faire*/
    } else {
      if ($user_id != $UserID) {
        return response()->json(false);
        $success=false;
        $msg="Error.";
        $di_ot=null;
      }
    }
  }


    if($success){
    $now = Carbon::now();

    $form = $request->input('form');
    $intervenants = $request->input('intervenants');

    $date = $this->DateFormYMD($form['date']);
    $time = $this->TimeFormHM($form['time']);
    $form['date'] = $date;
    $form['time'] = $time;

    $date1 = new DateTime($date);
    $time1 = new DateTime($time);
    $datetime = new DateTime($date1->format('Y-m-d') . ' ' . $time1->format('H:i'));
    $form['datetime'] = $datetime;

    $enAttente = false;
    if ($form['statut'] == "enAttente") {
      $enAttente = true;
    }

    if ($enAttente) {
      $form['date_execution'] = $now;
      $form['statut'] = "enCours";
      $form['last_modif'] = null;
      $form['modifieur_user_id'] = null;
    } else {
      $form['last_modif'] = $now;
      $form['modifieur_user_id'] = $UserID;
    }

    $di_ot = di_ot::where('OtID', '=', $id)->update($form);

    if ($enAttente) {
      $ot = di_ot::where('OtID', '=', $id)->first();
      $DiID = $ot->di_id;
      di_ot::where('OtID', '=', $id)->update(['updated_at' => $ot->created_at]);
      di_di::where('DiID', '=', $DiID)->update(['statut' => 'enCours']);
    } else {
      di_ot::where('OtID', '=', $id)->increment('NbDeModif', 1);
    }


    // intervenants 
    di_ot_intervenant::where('ot_id', '=', $id)->delete();
    foreach ($intervenants as $IntervenantID) {
      di_ot_intervenant::create([
        'ot_id' => $id,
        'intervenant_id' => $IntervenantID
      ]);
    }

  }
    //return response()->json($di_ot);

    return response()->json(['success'=>$success,'msg'=>$msg]);


  }


  public function deleteOt($id){
    $OtpID=$id;
    
    $OT_DATA=$this->OT_DATA($id);
    if( !( $OT_DATA->exist && ( $OT_DATA->statut=='enAttente' || $OT_DATA->statut=='enCours') ) ){
      return response()->json(['success'=>false,'result'=>null]);
    }
    
    // suppression des bsms enAttente and bso ouvert
    $di_bsms=di_bsm::where('ot_id','=',$OtpID)->where('statut','=','enAttente')->get();
    foreach($di_bsms as $di_bsm){
     $BsmID=$di_bsm['BsmID'];
     di_bsm_det::where('bsm_id','=',$BsmID)->delete();
    }
    di_bsm::where('ot_id','=',$OtpID)->where('statut','=','enAttente')->delete();
    $di_bsos=di_bso::where('ot_id','=',$OtpID)->where('isCorrectif','=',1)->where('statut','=','ouvert')->get();
    foreach($di_bsos as $di_bso){
     $BsoID=$di_bso['BsoID'];
     di_bso_det::where('bso_id','=',$BsoID)->delete();
    }
    di_bso::where('ot_id','=',$OtpID)->where('isCorrectif','=',1)->where('statut','=','ouvert')->delete();
    


    // lazim tarja3 tetteb3ath retour lel magsini féha somme mte3 les bso kolll fi retour wa7da
    $articles=Array();
    $articles_bsm=$this->getUnionBsmsOt($OtpID);

    $articles_r=$articles_bsm;

    
    foreach($articles_bsm as $article_bsm){
      $article_id_bsm=$article_bsm->article_id;
      $des=$article_bsm->des;
      $qted=$article_bsm->qted;
      $qtea=$article_bsm->qtea;
      foreach($articles_r as $article_r){
          $article_id_r=$article_r->article_id;
          $qtear=$article_r->qtea;
        if($article_id_bsm == $article_id_r){
          $value = new \stdClass();
          $value->article_id = $article_id_bsm;
          $value->des = $des;
          $value->qted = $qted;
          $value->qtea = $qtea;
          $value->qteu = $qtea - $qtear ;
          //$value->qtear = $article_r['qtear'] ; 
          //$value->qter = null;
          //$value->qtep = 0;
          array_push($articles,$value);
        }
      }
    }

    $isRetour=false;
    foreach($articles as $article){
     if($article->qtea > $article->qteu ){
        $isRetour=true;
     }
    }
   $array_pour_LancerNouveauRetour=Array();
   if($isRetour){
    foreach($articles as $article){
      
      if($article->qtea > $article->qteu ){
         $value = new \stdClass();
         $value->article_id = $article->article_id;
         $value->qtear = $article->qtea - $article->qteu;
         array_push($array_pour_LancerNouveauRetour,$value);
      }
     }
     $RetourID=$this->LancerNouveauRetour(NULL,$array_pour_LancerNouveauRetour,$OtpID);
     if(!$RetourID){$success=false;}
   }
      
      
      $di_ot=di_ot::where('OtID','=',$OtpID)->first();
      $DiID=$di_ot->di_id;
      $di_ot_result=di_ot::where('OtID','=',$OtpID)->update(['exist'=>false]);
      di_ot_intervenant::where('ot_id','=',$OtpID)->delete();
      di_di_plan::where('di_id', '=', $DiID)->delete();

      di_di::where('DiID','=',$DiID)->update(['statut'=>'enAttente']);


      
      return response()->json(['success'=>true,'result'=>$di_ot_result]);
      
  }


  public function getBsmByID(Request $request,$id)
  {

    $Src = $request->Src;
    if($Src=='correctif'){

    di_bsm::where('BsmID', '=', $id)->first();
    $di_bsm_dets = di_bsm_det::where('bsm_id', '=', $id)
      ->join('articles', function ($join) {
        $join->on('articles.ArticleID', '=', 'di_bsm_dets.article_id');
      })
      ->join('unites', function ($join) {
        $join->on('unites.UniteID', '=', 'articles.unite_id');
      })
      ->select('di_bsm_dets.*', 'articles.des', 'articles.stock', 'unites.unite')
      ->get();

    // DeletedBsmArticleIDs
    // BsmArticles 

    $Bsm = new \stdClass();
    $Bsm->DeletedBsmDetIDs = "";
    $Bsm->BsmArticles = $di_bsm_dets;

    return response()->json($Bsm);

    }

    if($Src=='preventif'){

      prev_bsm::where('BsmID', '=', $id)->first();
      $di_bsm_dets = prev_bsm_det::where('bsm_id', '=', $id)
        ->join('articles', function ($join) {
          $join->on('articles.ArticleID', '=', 'prev_bsm_dets.article_id');
        })
        ->join('unites', function ($join) {
          $join->on('unites.UniteID', '=', 'articles.unite_id');
        })
        ->select('prev_bsm_dets.*', 'articles.des', 'articles.stock', 'unites.unite')
        ->get();
  
      // DeletedBsmArticleIDs
      // BsmArticles 
  
      $Bsm = new \stdClass();
      $Bsm->DeletedBsmDetIDs = "";
      $Bsm->BsmArticles = $di_bsm_dets;
  
      return response()->json($Bsm);
  
      }


  }

  public function getBsoByID(Request $request,$id)
  {

    $Src = $request->Src;
    //if($Src=='correctif'){

    di_bso::where('BsoID', '=', $id)->first();
    $di_bso_dets = di_bso_det::where('bso_id', '=', $id)
      ->join('art_outils', function ($join) {
        $join->on('art_outils.OutilID', '=', 'di_bso_dets.outil_id')
          ->where('art_outils.exist', '=', 1);
      })
      ->select('di_bso_dets.*', 'art_outils.des')
      ->get();

    // DeletedBsmArticleIDs
    // BsmArticles 

    $Bso = new \stdClass();
    $Bso->DeletedBsmDetIDs = "";
    $Bso->BsoArticles = $di_bso_dets;

    return response()->json($Bso);
    //}


    /*
    if($Src=='preventif'){

      prev_bso::where('BsoID', '=', $id)->first();
      $di_bso_dets = prev_bso_det::where('bso_id', '=', $id)
        ->join('art_outils', function ($join) {
          $join->on('art_outils.OutilID', '=', 'prev_bso_dets.outil_id')
            ->where('art_outils.exist', '=', 1);
        })
        ->select('prev_bso_dets.*', 'art_outils.des')
        ->get();
  
      // DeletedBsmArticleIDs
      // BsmArticles 
  
      $Bso = new \stdClass();
      $Bso->DeletedBsmDetIDs = "";
      $Bso->BsoArticles = $di_bso_dets;
  
      return response()->json($Bso);
      }
  */

  }

  public function BSM_DATA($cp,$BsmID){

    $exist=0;
    $statut='';
    $ot_exist=0;
    $canEdit=0;
    $canDelete=0;
    $ot_statut='';
    $isTouched=0;
    
    if($cp=='c'){
      
    $bsm=di_bsm::where('BsmID', '=', $BsmID)->where('exist','=',1)->first();
    if($bsm){
      $exist=1;
      $statut=$bsm->statut;
      if($statut!='enAttente'){$isTouched=1;}
      $OtID=$bsm->ot_id;
      $OT_DATA=$this->OT_DATA($OtID);
      if(  $OT_DATA->exist && $OT_DATA->statut=='enCours' && !$isTouched){
        $canEdit=1;
        $canDelete=1;
      }
    }

    }


    $BSM_DATA=new \stdClass();
    $BSM_DATA->exist=$exist;
    $BSM_DATA->statut=$statut;
    $BSM_DATA->ot_exist=$ot_exist;
    $BSM_DATA->ot_statut=$ot_statut;

    $BSM_DATA->canEdit=$canEdit;
    $BSM_DATA->canDelete=$canDelete;
    $BSM_DATA->isTouched=$isTouched;

    return $BSM_DATA;

  }

  public function OTP_DATA($OtoID){
    /*
    $exist=0;
    $statut='';
    
    $di_ot_test = di_ot::where('OtID', '=', $OtID)->where('exist','=',1)->first();
    if($di_ot_test){ 
      $exist=1 ;
      $statut=$di_ot_test->statut;
    }

    $OT_DATA=new \stdClass();
    $OT_DATA->exist=$exist;
    $OT_DATA->statut=$statut;

    return $OT_DATA;
  */

  }

  public function addBsm(Request $request, $id)
  {
    // lazim ot exist w enCours
    // 

    $Src = $request->Src;

    if($Src=='correctif'){
    $success = true;

    $UserID = JWTAuth::user()->id;
    

    $OT_DATA=$this->OT_DATA($id);
    if( ! ($OT_DATA->exist && $OT_DATA->statut=='enCours') ){
      return response()->json(['success' => false, 'bsm' => null]);
    }

    $di_bsm = di_bsm::create([
      'ot_id' => $id,
      'user_id' => $UserID,
    ]);
    $BsmID = $di_bsm->BsmID;
    $bsm = di_bsm::where('BsmID', '=', $BsmID)->first();

    if (!$di_bsm) {
      $success = false;
    }



    $BsmArticles = $request->BsmArticles;
    foreach ($BsmArticles as $article) {
      $Qte = $article['qte'];
      $ArticleID = $article['article_id'];
      $motif = $article['motif'];

      $di_bsm_det = di_bsm_det::create([
        'bsm_id' => $BsmID,
        'article_id' => $ArticleID,
        'qte' => $Qte,
        'motif' => $motif
      ]);

      if (!$di_bsm_det) {
        $success = false;
      }
    }

    return response()->json(['success' => $success, 'bsm' => $bsm]);
    }

    
    if($Src=='preventif'){
      $success = true;
  
      $UserID = JWTAuth::user()->id;
  
      $di_bsm = prev_bsm::create([
        'otp_id' => $id,
        'user_id' => $UserID,
      ]);
      $BsmID = $di_bsm->BsmID;
      $bsm = prev_bsm::where('BsmID', '=', $BsmID)->first();
  
      if (!$di_bsm) {
        $success = false;
      }
  
  
  
      $BsmArticles = $request->BsmArticles;
      foreach ($BsmArticles as $article) {
        $Qte = $article['qte'];
        $ArticleID = $article['article_id'];
        $motif = $article['motif'];
  
        $di_bsm_det = prev_bsm_det::create([
          'bsm_id' => $BsmID,
          'article_id' => $ArticleID,
          'qte' => $Qte,
          'motif' => $motif
        ]);
  
        if (!$di_bsm_det) {
          $success = false;
        }
      }
  
      return response()->json(['success' => $success, 'bsm' => $bsm]);
      }



  }

  public function addBso(Request $request, $id)
  {

    $Src=$request->Src;

    if($Src=='correctif'){ $isCorrectif=true;}
    else if($Src=='preventif'){ $isCorrectif=false;}
    else{$isCorrectif=null;}

    $success = true;
    $UserID = JWTAuth::user()->id;

    $di_bso = di_bso::create([
      'ot_id' => $id,
      'isCorrectif' => $isCorrectif,
      'user_id' => $UserID,
    ]);
    $BsoID = $di_bso->BsoID;
    $bso = di_bso::where('BsoID', '=', $BsoID)->first();

    if (!$di_bso) {
      $success = false;
    }


    $BsoArticles = $request->BsoArticles;
    foreach ($BsoArticles as $article) {
      $estimation = $article['estimation'];
      $OutilID = $article['outil_id'];
      $periode = $article['periode'];

      $di_bso_det = di_bso_det::create([
        'bso_id' => $BsoID,
        'outil_id' => $OutilID,
        'estimation' => $estimation,
        'periode' => $periode
      ]);

      if (!$di_bso_det) {
        $success = false;
      }
    }

    return response()->json(['success' => $success, 'bso' => $bso]);

  }



  public function editBsm(Request $request, $id)
  {

    $Src = $request->Src;
    if($Src=='correctif'){
    $success = true;

    $BSM_DATA=$this->BSM_DATA('c',$id);
    if(!$BSM_DATA->canEdit){
      return response()->json(false);
    }

    $BsmArticles = $request->BsmArticles;
    $DeletedBsmDetIDs = $request->DeletedBsmDetIDs;



    foreach ($BsmArticles as $article) {

      if ($article['BsmDetID'] == null) {

        $Qte = $article['qte'];
        $ArticleID = $article['article_id'];
        $motif = $article['motif'];

        $di_bsm_det = di_bsm_det::create([
          'bsm_id' => $id,
          'article_id' => $ArticleID,
          'qte' => $Qte,
          'motif' => $motif
        ]);

        if (!$di_bsm_det) {
          $success = false;
        }
      }

      if ($article['BsmDetID'] > 0) {

        $Qte = $article['qte'];
        $ArticleID = $article['article_id'];
        $motif = $article['motif'];

        $di_bsm_det = di_bsm_det::where('BsmDetID', '=', $article['BsmDetID'])->update([
          'article_id' => $ArticleID,
          'qte' => $Qte,
          'motif' => $motif
        ]);
      }
    }

    $arrIDsToDelete = explode(',', $DeletedBsmDetIDs, -1);
    foreach ($arrIDsToDelete as $ID) {
      $di_bsm_det = di_bsm_det::where('BsmDetID', '=', $ID)->delete();
      if (!$di_bsm_det) {
        $success = false;
      }
    }


    return response()->json($success);

  }

  if($Src=='preventif'){
    $success = true;

    $BsmArticles = $request->BsmArticles;
    $DeletedBsmDetIDs = $request->DeletedBsmDetIDs;



    foreach ($BsmArticles as $article) {

      if ($article['BsmDetID'] == null) {

        $Qte = $article['qte'];
        $ArticleID = $article['article_id'];
        $motif = $article['motif'];

        $di_bsm_det = prev_bsm_det::create([
          'bsm_id' => $id,
          'article_id' => $ArticleID,
          'qte' => $Qte,
          'motif' => $motif
        ]);

        if (!$di_bsm_det) {
          $success = false;
        }
      }

      if ($article['BsmDetID'] > 0) {

        $Qte = $article['qte'];
        $ArticleID = $article['article_id'];
        $motif = $article['motif'];

        $di_bsm_det = prev_bsm_det::where('BsmDetID', '=', $article['BsmDetID'])->update([
          'article_id' => $ArticleID,
          'qte' => $Qte,
          'motif' => $motif
        ]);
      }
    }

    $arrIDsToDelete = explode(',', $DeletedBsmDetIDs, -1);
    foreach ($arrIDsToDelete as $ID) {
      $di_bsm_det = prev_bsm_det::where('BsmDetID', '=', $ID)->delete();
      if (!$di_bsm_det) {
        $success = false;
      }
    }


    return response()->json($success);

  }


  }


  public function deleteBsm(Request $request,$id){
   $cp=$request->input('cp');
   if($cp=='preventif'){
     $success=true;
     prev_bsm_det::where('bsm_id','=',$id)->delete();
     prev_bsm::where('BsmID','=',$id)->delete();
     return response()->json(['success'=>$success]);
   }

   if($cp=='correctif'){
    $success=true;
    di_bsm_det::where('bsm_id','=',$id)->delete();
    di_bsm::where('BsmID','=',$id)->delete();
    return response()->json(['success'=>$success]);
  }

  }

  public function editBso(Request $request, $id)
  {

    $success = true;

    $BsoArticles = $request->BsoArticles;
    $DeletedBsoDetIDs = $request->DeletedBsoDetIDs;

    $new_arr = array();
    $deleted_arr = array();

    foreach ($BsoArticles as $article) {

      if ($article['BsoDetID'] == null) {

        $estimation = $article['estimation'];
        $OutilID = $article['outil_id'];
        $periode = $article['periode'];

        $di_bso_det = di_bso_det::
          // where('BsoDetID','=',$article['BsoDetID'])-> // bizard !!! 
          create([
            'bso_id' => $id,
            'outil_id' => $OutilID,
            'estimation' => $estimation,
            'periode' => $periode
          ]);

        if (!$di_bso_det) {
          $success = false;
        } else {
          array_push($new_arr, $di_bso_det);
        }
      }

      if ($article['BsoDetID'] > 0) {

        $estimation = $article['estimation'];
        $OutilID = $article['outil_id'];
        $periode = $article['periode'];

        $di_bso_det = di_bso_det::where('BsoDetID', '=', $article['BsoDetID'])->update([
          'outil_id' => $OutilID,
          'estimation' => $estimation,
          'periode' => $periode
        ]);
      }
    }

    $arrIDsToDelete = explode(',', $DeletedBsoDetIDs, -1);
    foreach ($arrIDsToDelete as $ID) {
      $di_bso_det_ret = di_bso_det::where('BsoDetID', '=', $ID)->first();
      $OutilID = $di_bso_det_ret->outil_id;
      art_outil::where('OutilID', '=', $OutilID)->update(['reserve' => 0]);

      di_response_bso_det::where('bso_det_id', '=', $ID)->delete();
      $di_bso_det = di_bso_det::where('BsoDetID', '=', $ID)->delete();
      if (!$di_bso_det) {
        $success = false;
      } else {
        array_push($deleted_arr, $di_bso_det_ret);
      }
    }


    return response()->json(['success' => $success, 'new_arr' => $new_arr, 'deleted_arr' => $deleted_arr]);
  }


  

  
  public function deleteBso(Request $request,$id){
    $cp=$request->input('cp');
    if($cp=='preventif' || $cp=='correctif'){
      $success=true;
      di_bso_det::where('bso_id','=',$id)->delete();
      di_bso::where('BsoID','=',$id)->delete();
      return response()->json(['success'=>$success]);
    }
 
  }


  public function getBsms($id)
  {
    $di_bsms = di_bsm::where('ot_id', '=', $id)->get();
    return response()->json($di_bsms);
  }

  /* mabda2iyan zayda just sta3maltha f test 
  public function getBsos($id)
  {
    $di_bsos = di_bso::where('ot_id', '=', $id)->get(); // fil intithar
    return response()->json($di_bsos);
  }
  */


  public function getBsmWithResponse(Request $request,$id)
  {

    $Src=$request->Src;
    if($Src=='correctif'){

    $di_bsm = di_bsm::where('BsmID', '=', $id)

      ->join('di_ots', function ($join) {
        $join->on('di_ots.OtID', '=', 'di_bsms.ot_id');
      })->join('di_dis', function ($join) {
        $join->on('di_dis.DiID', '=', 'di_ots.di_id');
      })->join('users as recepteur', function ($join) {
        $join->on('recepteur.id', '=', 'di_dis.recepteur_user_id');
      })

      ->join('users', function ($join) {
        $join->on('users.id', '=', 'di_bsms.user_id');
      })
      ->select('di_bsms.*', 'users.name', 'di_dis.recepteur_user_id', 'recepteur.name as recepteur')->first();

    $di_bsm_dets = di_bsm_det::where('bsm_id', '=', $id)
      ->join('articles', function ($join) {
        $join->on('articles.ArticleID', '=', 'di_bsm_dets.article_id');
      })
      ->join('unites', function ($join) {
        $join->on('unites.UniteID', '=', 'articles.unite_id');
      })
      ->select('di_bsm_dets.*', 'articles.des','unites.unite')->get();

    $di_response_bsm = di_response_bsm::where('bsm_id', '=', $id)
      ->join('users', function ($join) {
        $join->on('users.id', '=', 'di_response_bsms.user_id');
      })->select('di_response_bsms.*', 'users.name')->first();

    if ($di_response_bsm) {

      $ResponseBsmID = $di_response_bsm->ResponseBsmID;

      $di_response_bsm_dets = di_response_bsm_det::where('response_bsm_id', '=', $ResponseBsmID)

        ->join('di_bsm_dets', function ($join) {
          $join->on('di_bsm_dets.BsmDetID', '=', 'di_response_bsm_dets.bsm_det_id');
        })
        ->join('articles', function ($join) {
          $join->on('articles.ArticleID', '=', 'di_bsm_dets.article_id');
        })
        ->join('unites', function ($join) {
          $join->on('unites.UniteID', '=', 'articles.unite_id');
        })
        ->select('di_response_bsm_dets.*', 'articles.des','unites.unite','di_bsm_dets.article_id')
        ->get();

      $combined = array();
      foreach ($di_bsm_dets as $keybsm => $bsm) {
        $article_id_bsm = $bsm['article_id'];
        $obj = new \stdClass();
        foreach ($di_response_bsm_dets as $keyresponse => $response) {
          $article_id_response = $response['article_id'];
          if ($article_id_bsm == $article_id_response) {
            $obj->bsm = $bsm;
            $obj->response = $response;
          }
        }
        array_push($combined, $obj);
      }



      return response()->json([
        'bsm' => $di_bsm,
        'response_bsm' => $di_response_bsm,
        'bsm_dets' => $di_bsm_dets,
        'response_bsm_dets' => $di_response_bsm_dets,
        'combined' => $combined
      ]);
    } else {
      return response()->json([
        'bsm' => $di_bsm,
        'response_bsm' => $di_response_bsm,
        'bsm_dets' => $di_bsm_dets,
        'response_bsm_dets' => [],
        'combined' => []
      ]);
    }

    }


    if($Src=='preventif'){

      $di_bsm = prev_bsm::where('BsmID', '=', $id)
        ->join('prev_otps', function ($join) {
          $join->on('prev_otps.OtpID', '=', 'prev_bsms.otp_id');
        })
        ->join('users as recepteur', function ($join) {
          $join->on('recepteur.id', '=', 'prev_otps.user_id');
        })
        ->join('users', function ($join) {
          $join->on('users.id', '=', 'prev_bsms.user_id');
        })
        ->select('prev_bsms.*', 'users.name', 'prev_otps.user_id as recepteur_user_id', 'recepteur.name as recepteur')
        ->first();

      $di_bsm_dets = prev_bsm_det::where('bsm_id', '=', $id)
        ->join('articles', function ($join) {
          $join->on('articles.ArticleID', '=', 'prev_bsm_dets.article_id');
        })
        ->join('unites', function ($join) {
          $join->on('unites.UniteID', '=', 'articles.unite_id');
        })
        ->select('prev_bsm_dets.*','unites.unite', 'articles.des')->get();
  
      $di_response_bsm = prev_response_bsm::where('bsm_id', '=', $id)
        ->join('users', function ($join) {
          $join->on('users.id', '=', 'prev_response_bsms.user_id');
        })->select('prev_response_bsms.*', 'users.name')->first();
  
      if ($di_response_bsm) {
  
        $ResponseBsmID = $di_response_bsm->ResponseBsmID;
  
        $di_response_bsm_dets = prev_response_bsm_det::where('response_bsm_id', '=', $ResponseBsmID)
  
          ->join('prev_bsm_dets', function ($join) {
            $join->on('prev_bsm_dets.BsmDetID', '=', 'prev_response_bsm_dets.bsm_det_id');
          })
          ->join('articles', function ($join) {
            $join->on('articles.ArticleID', '=', 'prev_bsm_dets.article_id');
          })
          ->join('unites', function ($join) {
            $join->on('unites.UniteID', '=', 'articles.unite_id');
          })
          ->select('prev_response_bsm_dets.*', 'articles.des', 'unites.unite', 'prev_bsm_dets.article_id')
          ->get();
  
        $combined = array();
        foreach ($di_bsm_dets as $keybsm => $bsm) {
          $article_id_bsm = $bsm['article_id'];
          $obj = new \stdClass();
          foreach ($di_response_bsm_dets as $keyresponse => $response) {
            $article_id_response = $response['article_id'];
            if ($article_id_bsm == $article_id_response) {
              $obj->bsm = $bsm;
              $obj->response = $response;
            }
          }
          array_push($combined, $obj);
        }
  
  
  
        return response()->json([
          'bsm' => $di_bsm,
          'response_bsm' => $di_response_bsm,
          'bsm_dets' => $di_bsm_dets,
          'response_bsm_dets' => $di_response_bsm_dets,
          'combined' => $combined
        ]);
      } else {
        return response()->json([
          'bsm' => $di_bsm,
          'response_bsm' => $di_response_bsm,
          'bsm_dets' => $di_bsm_dets,
          'response_bsm_dets' => [],
          'combined' => []
        ]);
      }
  
      }

  
  }


  public function getBsoWithResponse(Request $request,$id)
  {

    $Src=$request->Src;
    

    $bsoWithNullOtID = di_bso::where('BsoID', '=', $id)
      ->join('users', function ($join) {
        $join->on('users.id', '=', 'di_bsos.user_id');
      })
      ->select('di_bsos.*', 'users.name')->first();

    $UseID=null;
    $use=null;
    if(!$bsoWithNullOtID->ot_id){
      $art_2_use=art_2_use::where('bso_id','=',$id)->first();
      $UseID=$art_2_use->UseID;
      $use=art_2_use::where('bso_id','=',$id)
      ->join('users', function ($join) {
        $join->on('users.id', '=', 'art_2_uses.user_id');
      })
      ->join('intervenants', function ($join) {
        $join->on('intervenants.IntervenantID', '=', 'art_2_uses.intervenant_id');
      })
      ->select('art_2_uses.*','users.name','intervenants.name as intervenant')
      ->first();
    }

    if($Src=='correctif'){
    $di_bso = di_bso::where('BsoID', '=', $id)
      ->join('di_ots', function ($join) {
        $join->on('di_ots.OtID', '=', 'di_bsos.ot_id');
      })->join('di_dis', function ($join) {
        $join->on('di_dis.DiID', '=', 'di_ots.di_id');
      })->join('users as recepteur', function ($join) {
        $join->on('recepteur.id', '=', 'di_dis.recepteur_user_id');
      })
      ->join('users', function ($join) {
        $join->on('users.id', '=', 'di_bsos.user_id');
      })
      ->select('di_bsos.*', 'users.name', 'di_dis.recepteur_user_id', 'recepteur.name as recepteur')->first();
    }
    else if($Src=='preventif'){
      $di_bso = di_bso::where('BsoID', '=', $id)
        ->join('prev_otps', function ($join) {
          $join->on('prev_otps.OtpID', '=', 'di_bsos.ot_id');
        })->join('users as recepteur', function ($join) {
          $join->on('recepteur.id', '=', 'prev_otps.user_id');
        })
        ->join('users', function ($join) {
          $join->on('users.id', '=', 'di_bsos.user_id');
        })
        ->select('di_bsos.*', 'users.name', 'prev_otps.user_id as recepteur_user_id', 'recepteur.name as recepteur')->first();
      }
      else{
        $di_bso=null;
      }
    
    $di_bso_dets = di_bso_det::where('bso_id', '=', $id)
      ->join('art_outils', function ($join) {
        $join->on('art_outils.OutilID', '=', 'di_bso_dets.outil_id');
      })->select('di_bso_dets.*', 'art_outils.des')->get();

    $di_response_bso = di_response_bso::where('bso_id', '=', $id)
      ->join('users', function ($join) {
        $join->on('users.id', '=', 'di_response_bsos.user_id');
      })->select('di_response_bsos.*', 'users.name')->first();

    if ($di_response_bso) {

      $ResponseBsoID = $di_response_bso->ResponseBsoID;

      $di_response_bso_dets = di_response_bso_det::where('response_bso_id', '=', $ResponseBsoID)

        ->join('di_bso_dets', function ($join) {
          $join->on('di_bso_dets.BsoDetID', '=', 'di_response_bso_dets.bso_det_id');
        })
        ->join('art_outils', function ($join) {
          $join->on('art_outils.OutilID', '=', 'di_bso_dets.outil_id');
        })
        ->select('di_response_bso_dets.*', 'art_outils.des', 'di_bso_dets.outil_id')
        ->get();

      $combined = array();
      foreach ($di_bso_dets as $keybsm => $bso) {
        $outil_id_bso = $bso['outil_id'];
        $obj = new \stdClass();
        foreach ($di_response_bso_dets as $keyresponse => $response) {
          $outil_id_response = $response['outil_id'];
          if ($outil_id_bso == $outil_id_response) {
            $obj->bso = $bso;
            $obj->response = $response;
          }
        }
        array_push($combined, $obj);
      }



      return response()->json([
        'bso' => $di_bso,
        'response_bso' => $di_response_bso,
        'bso_dets' => $di_bso_dets,
        'response_bso_dets' => $di_response_bso_dets,
        'combined' => $combined,
        'bsoWithNullOtID' => $bsoWithNullOtID,
        'UseID'=>$UseID,
        'use'=>$use
        
      ]);
    } else {
      return response()->json([
        'bso' => $di_bso,
        'response_bso' => $di_response_bso,
        'bso_dets' => $di_bso_dets,
        'response_bso_dets' => [],
        'combined' => [],
        'bsoWithNullOtID' => $bsoWithNullOtID,
        'UseID'=>$UseID,
        'use'=>$use

      ]);
    }
  }



  public function planifierDi(Request $request, $id)
  {

    $success = true;
    $PlanID = $id;
    $form = $request->input('form');

    $DiID = $form['DiID'];

    $date = $this->DateFormYMD($form['date']);
    $time = $this->TimeFormHM($form['time']);
    $form['date'] = $date;
    $form['time'] = $time;

    $date1 = new DateTime($date);
    $time1 = new DateTime($time);
    $datetime = new DateTime($date1->format('Y-m-d') . ' ' . $time1->format('H:i'));
    $form['datetime'] = $datetime;

    $UserID = JWTAuth::user()->id;

    // test logique
    // ma tsirich creation mte3 plan fih exist = 0
    // ma tnajjamch ta3mil plan a5ir el nafs el di


    $di_di_test=di_di::where('DiID', '=', $DiID)->where('exist','=',true)->first();
    if(!$di_di_test){return response()->json(false);}

    if (!($id > 0)) {

      // tester si il ya un plan avec di_id = DiID
      $testIfExistPlanWithThisDiID=di_di_plan::where('di_id', '=', $DiID)->get();
      if(count($testIfExistPlanWithThisDiID)>0){return response()->json(false);}
      
      $di_di_plan = di_di_plan::create([
        'user_id' => $UserID,
        'di_id' => $DiID,
        'date' => $form['date'],
        'time' => $form['time'],
        'datetime' => $form['datetime'],
        'type' => $form['type'],
      ]);
      if (!$di_di_plan) {
        $success = false;
      }

      $PlanID = $di_di_plan->PlanID;

      $res = di_di_plan::where('PlanID', '=', $PlanID)
        ->join('users', function ($join) {
          $join->on('users.id', '=', 'di_di_plans.user_id');
        })
        ->select('di_di_plans.*', 'users.name')
        ->first();
    } else {

      $di_di_plan0 = di_di_plan::where('PlanID', '=', $id)->where('exist','=',true)->first();
      if(!$di_di_plan0){ return response()->json(false);}

      $isExecuted = $di_di_plan0->isExecuted;
      if (!$isExecuted) {

        $last_modif = Carbon::now();
        $di_di_plan1 = di_di_plan::where('PlanID', '=', $id)->update([
          'date' => $form['date'],
          'time' => $form['time'],
          'datetime' => $form['datetime'],
          'type' => $form['type'],
          'last_modif' => $last_modif,
          'modifieur_user_id' => $UserID
        ]);
        $di_di_plan2 = di_di_plan::where('PlanID', '=', $id)->increment('NbDeModif', 1);
        if (!$di_di_plan2) {
          $success = false;
        }

        $res = di_di_plan::where('PlanID', '=', $id)
          ->join('users', function ($join) {
            $join->on('users.id', '=', 'di_di_plans.user_id');
          })
          ->select('di_di_plans.*', 'users.name')
          ->first();
      } else {
        $success = false;
      }
    }

    di_di::where('DiID', '=', $DiID)->update(['statut' => 'planifie']);


    if (!$success) {
      return response()->json($success);
    } else {
      return response()->json($res);
    }
  }

  public function getPlan($id)
  {
    $di_di_plan = di_di_plan::where('PlanID', '=', $id)->first();
    return response()->json($di_di_plan);
  }

  public function getPlans()
  {
    $di_di_plan = di_di_plan::join('users', function ($join) {
      $join->on('users.id', '=', 'di_di_plans.user_id');
    })
      ->select('di_di_plans.*', 'users.name')
      ->get();
    return response()->json($di_di_plan);
  }

  
  public function getPlansForList(Request $request)
  {

  $page = $request->input('page');
  $itemsPerPage = $request->input('itemsPerPage');
  $nodes = $request->input('nodes');

  $skipped = ($page - 1) * $itemsPerPage;
  $endItem = $skipped + $itemsPerPage;

  $filter = $request->input('filter');
  $datemin = $filter['datemin'];
  $datemax = $filter['datemax'];
  $datemin = app('App\Http\Controllers\Divers\generale')->ReglageDateMinDateMax($datemin, 'datemin');
  $datemax = app('App\Http\Controllers\Divers\generale')->ReglageDateMinDateMax($datemax, 'datemax');
  $searchFilterText = $filter['searchFilterText'];

  $type = $filter['type'];
  $degre = $filter['degre'];
  //$statut = $filter['statut'];
  $UserID = JWTAuth::user()->id;
  $me = app('App\Http\Controllers\Divers\generale')->getUserPosts($UserID);
  $me = $me->original;
  $me = $me[0];
  $posts = $me->posts;

  $isAdmin = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, ['Admin']);
  $isMethode = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, ['Methode']);
  $OthersAuthorized = ['ChefDeEquipe', 'ChefDePoste', 'ResponsableMaintenance'];
  $isOthersAuthorized = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, $OthersAuthorized);

  $dis = di_di_plan::whereDate('di_di_plans.datetime', '>=', $datemin)
    ->whereDate('di_di_plans.datetime', '<=', $datemax)
    
    ->where('di_di_plans.isExecuted','=',0)

    /*
    ->whereIn('di_dis.type', $type)
    ->whereIn('di_dis.degre', $degre)
    ->whereIn('di_dis.statut', $statut)
    */
    /*
    ->Where(function ($query) use ($isAdmin, $isMethode, $isOthersAuthorized, $UserID) {
      if (($isAdmin || $isMethode)) {
        return $query;
      } else if ($isOthersAuthorized) {
        return $query->where('di_dis.user_id', '=', $UserID)
          ->orWhere('demandeur_user_id', '=', $UserID)
          ->orWhere('recepteur_user_id', '=', $UserID);
      }
    })
    */
    
    ->leftjoin('di_dis', function ($join) {
      $join->on('di_dis.DiID', '=', 'di_di_plans.di_id');
    })
    ->leftjoin('di_ots', function ($join) {
      $join->on('di_ots.di_id', '=', 'di_dis.DiID');
    })
    ->join('users as demandeur', function ($join) {
      $join->on('demandeur.id', '=', 'di_dis.demandeur_user_id');
    })
    ->join('users as recepteur', function ($join) {
      $join->on('recepteur.id', '=', 'di_dis.recepteur_user_id');
    })
    ->join('users', function ($join) {
      $join->on('users.id', '=', 'di_dis.user_id');
    })
    ->join('equi_equipements', function ($join) use ($nodes) {
      if (count($nodes) == 0) {
        $join->on('equi_equipements.EquipementID', '=', 'di_dis.equipement_id');
      } else {
        $join->on('equi_equipements.EquipementID', '=', 'di_dis.equipement_id')
          ->whereIn('equi_equipements.EquipementID', $nodes);
      }
    })
    ->join('equi_anomalies', function ($join) {
      $join->on('equi_anomalies.AnomalieID', '=', 'di_dis.anomalie_id');
    })
    ->Where(function ($query) use ($searchFilterText) {
      return $query
        ->where('equi_anomalies.anomalie', 'like', '%' . $searchFilterText . '%')
        ->orWhere('equi_equipements.equipement', 'like', '%' . $searchFilterText . '%')
        ->orWhere('recepteur.name', 'like', '%' . $searchFilterText . '%')
        ->orWhere('demandeur.name', 'like', '%' . $searchFilterText . '%')
        ->orWhere('users.name', 'like', '%' . $searchFilterText . '%');
    })

    ->whereIn('di_dis.type', $type)
    ->whereIn('di_dis.degre', $degre)
    //->whereIn('di_dis.statut', $statut)

    ->select('di_di_plans.*','di_dis.DiID', 'di_ots.OtID', 'users.name', 'recepteur.name as recepteur', 'demandeur.name as demandeur', 'equi_equipements.equipement', 'equi_anomalies.anomalie')
    ->orderBy('di_di_plans.datetime', 'desc');

  $countQuery = $dis->count();
  $dis = $dis->skip($skipped)->take($itemsPerPage)->get();

  return response()->json(['plans' => $dis, 'me' => $me, 'total' => $countQuery]);

}

  public function deplanifierDi($id)
  {
    $success = true;
    $msg="Di est deplanifiée avec success .";

    $di_di_plan0 = di_di_plan::where('PlanID', '=', $id)->first();

  if($di_di_plan0){
    $DiID = $di_di_plan0->di_id;
    $isExecuted = $di_di_plan0->isExecuted;
    if (!$isExecuted) {
      di_di_plan::where('PlanID', '=', $id)->where('isExecuted', '=', 0)->delete();
      di_di::where('DiID', '=', $DiID)->update(['statut' => 'enAttente']);
    }
  }else{
    $success=false; $msg="Error";
  }

    return response()->json(['success' => $success,'msg'=>$msg]);
  }


  public function executerPlan($id)
  {
    $UserID = JWTAuth::user()->id;
    $retour = $this->systemePlanAutoToOt($id, $UserID);
    return response()->json($retour);
  }


  public function systemePlanAutoToOt($id, $UseID)
  {
    $success = true;
    $msg="Di est executé avec succées . ";
    if ($UseID == 0) {
      $UseID = 1;
    }
    
    $di_di_plan = di_di_plan::where('PlanID', '=', $id)->where('isExecuted','=',0)->first();
    if(!$di_di_plan){
      $success=false; $msg="Error .";
      return ['success' => $success, 'ot' =>false,'msg'=>$msg];
    }

    $DiID = $di_di_plan->di_id;
   

    $req = new Request();
    $now = Carbon::now();
    $date = $this->DateFormYMD($now);
    $time = $this->TimeFormHM($now);

    $arr = ['user_id' => $UseID, 'di_id' => $DiID, 'date' => $date, 'time' => $time, 'datetime' => $now, 'statut' => 'enAttente'];
    $req->merge(['form' => $arr, 'intervenants' => []]);

    $ot = $this->addOt($req, 1);
    $ot = $ot->original;
    di_di::where('DiID', '=', $DiID)->update(['statut' => 'planifie']);

    $plan = di_di_plan::where('PlanID', '=', $id)->where('isExecuted', '=', 0)->first();
    $created_at = $plan->created_at;
    $NbDeModif = $plan->NbDeModif;
    if ($NbDeModif == 0) {
      di_di_plan::where('PlanID', '=', $id)->where('isExecuted', '=', 0)->update(['isExecuted' => 1, 'date_execution' => $now, 'updated_at' => $created_at]);
    } else {
      di_di_plan::where('PlanID', '=', $id)->where('isExecuted', '=', 0)->update(['isExecuted' => 1, 'date_execution' => $now]);
    }

    // notification
    if($UseID==1){

      $arr_ids_notified=Array();
        $di_obj=di_di::find($DiID);
        $ot_obj=di_ot::find($ot['ot']->OtID);

        $user_tn=User::find($di_di_plan->user_id); 
        //$user_tn->notify(new NewAutoDiExecuted($di_obj,$ot_obj)); 
        array_push($arr_ids_notified,$user_tn->id);
        if($di_di_plan->modifieur_user_id){
          $user_tn2=User::find($di_di_plan->modifieur_user_id); 
          if(!in_array($$user_tn2->id,$arr_ids_notified)){
          //$user_tn2->notify(new NewAutoDiExecuted($di_obj,$ot_obj)); 
          array_push($arr_ids_notified,$user_tn2->id);
          }
        }

        $user_c_di_id=$di_obj->user_id;  $user_c_di_tn=User::find($user_c_di_id);
        $user_d_di_id=$di_obj->demandeur_user_id; $user_d_di_tn=User::find($user_d_di_id);
        $user_r_di_id=$di_obj->recepteur_user_id; $user_r_di_tn=User::find($user_r_di_id);
       
        if(!in_array($user_c_di_id,$arr_ids_notified)){
          //$user_c_di_tn->notify(new NewAutoDiExecuted($di_obj,$ot_obj)); 
          array_push($arr_ids_notified,$user_c_di_id);
        }
        if(!in_array($user_d_di_id,$arr_ids_notified)){
          //$user_d_di_tn->notify(new NewAutoDiExecuted($di_obj,$ot_obj));
           array_push($arr_ids_notified,$user_d_di_id);
        }
        if(!in_array($user_r_di_id,$arr_ids_notified)){
          //$user_r_di_tn->notify(new NewAutoDiExecuted($di_obj,$ot_obj)); 
          array_push($arr_ids_notified,$user_r_di_id);
        }
    }

    return ['success' => $success, 'ot' => $ot , 'msg'=>$msg];
  }


  public function syestemePlans()
  {
    $tt=0;
    $now = Carbon::now();
    $di_di_plans = di_di_plan::where('isExecuted', '=', 0)->where('type', '=', 'auto')->get();
    foreach ($di_di_plans as $di_di_plan) {
      $datetime = $di_di_plan->datetime;
      $PlanID = $di_di_plan->PlanID;
      if ($now > $datetime) {
        $this->systemePlanAutoToOt($PlanID, 0);
        $tt++;
      }
    }
    //return response()->json($tt);
  }


  public function deletePlan($id)
  {

    $success = true;
    $di_di_plan = di_di_plan::where('PlanID', '=', $id)->first();
    $DiID = $di_di_plan->di_id;
    $update = di_di::where('DiID', '=', $DiID)->update(['statut' => 'enAttente']);
    if (!$update) {
      $success = false;
    }
    $delete = di_di_plan::where('PlanID', '=', $id)->delete();
    if (!$delete) {
      $success = false;
    }
    return response()->json($success);
  }


  public function getUnionBsmsOt($OtID)
  {
    $articles = array();
    $bsms = di_bsm::where('ot_id', '=', $OtID)->where('statut', '=', 'accepted')->where('exist', '=', 1)->get();

    foreach ($bsms as $bsm) {

      $BsmID = $bsm['BsmID'];

      $di_response_bsm = di_response_bsm::where('bsm_id', '=', $BsmID)->where('exist', '=', 1)->first();
      $ResponseBsmID = $di_response_bsm->ResponseBsmID;
      $di_response_bsm_dets = di_response_bsm_det::where('response_bsm_id', '=', $ResponseBsmID)
        ->join('di_bsm_dets', function ($join) {
          $join->on('di_bsm_dets.BsmDetID', '=', 'di_response_bsm_dets.bsm_det_id');
        })
        ->join('articles', function ($join) {
          $join->on('articles.ArticleID', '=', 'di_bsm_dets.article_id');
        })
        ->select('articles.des', 'di_bsm_dets.article_id','di_bsm_dets.qte as qted','di_response_bsm_dets.qte as qtea')
        ->get();

      foreach ($di_response_bsm_dets as $di_response_bsm_det) {
        $article_id = $di_response_bsm_det['article_id'];
        $des = $di_response_bsm_det['des'];
        $qted = $di_response_bsm_det['qted'];
        $qtea = $di_response_bsm_det['qtea'];

        if (isset($articles[$article_id])) {
          $value = (object) $articles[$article_id];
          $value->qted += $qted;
          $value->qtea += $qtea;
          $value->qteu += $qtea;//?
          $articles[$article_id] = $value;
        } else {
          $value = new \stdClass();
          $value->article_id = $article_id;
          $value->des = $des;
          $value->qted = $qted;
          $value->qtea = $qtea;
          $value->qteu = $qtea;
          $value->qter = 0;
          $articles[$article_id] = $value;
        }
      }
    }

    $articles=$this->correctIArray($articles);
    return $articles;
  }

  public function correctIArray($array){
    $ret_array=Array();
    foreach($array as $key => $value){
      array_push($ret_array,$value);
    }
    return $ret_array;
  }

  public function getBon(Request $request)
  {
    //return response()->json($request);
    $OtID = $request->input('OtID');
    $BonID = $request->input('BonID');
    $isBonExist = false;
    if ($OtID == 0) {
      $bon_id = di_bon::where('BonID', '=', $BonID)->first();
      $OtID = $bon_id->ot_id;
    } else {
      $NbDeBonsExist = di_bon::where('ot_id', '=', $OtID)->where('exist', '=', 1)->get();
      if (count($NbDeBonsExist) > 0) {
        $isBonExist = true;
      }
    }
    
    $articles=$this->getUnionBsmsOt($OtID);
    if ($BonID>0) {
      /*
      $di_bon_urps=di_bon_urp::where('bon_id','=',$BonID)->get();
      foreach($di_bon_urps as $di_bon_urp){
          foreach($articles as $article){
             if($di_bon_urp['article_id']==$article->article_id){
               $article->qteu=$di_bon_urp['qteu'];
             }
          }
      }
      */
      foreach($articles as $article){
        // qteu = TTqtea - TTqtear_ef
    // qter = TTqter_f
          $article->qteu= $article->qtea - $this->getTTqtear_efTTqter_f($BonID,$article->article_id)->TTqtear_ef;
          $article->qter= $this->getTTqtear_efTTqter_f($BonID,$article->article_id)->TTqter_f;
      }

    }
  



    $UserID = JWTAuth::user()->id;
    $me = app('App\Http\Controllers\Divers\generale')->getUserPosts($UserID);
    $me = $me->original;
    $me = $me[0];
    $posts = $me->posts;
    $isAdmin = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, ['Admin']);
    $isMethode = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, ['Methode']);

    $di_bon = "";

    $di_ot = di_ot::where('OtID', '=', $OtID)
      ->join('users', function ($join) {
        $join->on('users.id', '=', 'di_ots.user_id');
      })
      ->leftjoin('users as modifieur', function ($join) {
        $join->on('modifieur.id', '=', 'di_ots.modifieur_user_id');
      })
      ->join('di_dis', function ($join) {
        $join->on('di_dis.DiID', '=', 'di_ots.di_id');
      })
      ->leftjoin('equi_taches', function ($join) {
        $join->on('equi_taches.TacheID', '=', 'di_ots.tache_id');
      })
      ->join('users as demandeur', function ($join) {
        $join->on('demandeur.id', '=', 'di_dis.demandeur_user_id');
      })
      ->join('users as recepteur', function ($join) {
        $join->on('recepteur.id', '=', 'di_dis.recepteur_user_id');
      })
      ->join('equi_anomalies', function ($join) {
        $join->on('equi_anomalies.AnomalieID', '=', 'di_dis.anomalie_id');
      })
      ->leftjoin('equi_equipements', function ($join) { // nooooooooooooooooooooooooooode
        $join->on('equi_equipements.EquipementID', '=', 'di_dis.equipement_id');
      })
      ->Where(function ($query) use ($isAdmin, $isMethode, $UserID) {
        if (($isAdmin || $isMethode)) {
          return $query;
        } else {
          return $query->where('di_dis.recepteur_user_id', '=', $UserID);
          //->orWhere('di_dis.demandeur_user_id','=',$UserID);
        }
      })
      ->select(
        'di_ots.*',
        'users.name',
        'modifieur.name as modifieur_name',
        'equi_taches.tache',
        'equi_anomalies.anomalie',
        'recepteur.name as recepteur',
        'demandeur.name as demandeur',
        'di_dis.recepteur_user_id',
        'di_dis.demandeur_user_id',
        'di_dis.equipement_id',
        'equi_equipements.equipement',
        'equi_equipements.Niv'

      )
      ->first();

    //$di_bsos=di_bso::where('ot_id','=',$id)->get();
    //$di_bsms=di_bsm::where('ot_id','=',$id)->get();
    /*
        $intervevants=di_ot_intervenant::where('ot_id','=',$id)
        ->select('di_ot_intervenants.intervenant_id')
        ->get();
        */

    $EquipementID = $di_ot->equipement_id;
    $niveaux = app('App\Http\Controllers\Equipement\equipement')->getNiveausCompletDetByEquipementID($EquipementID);
    $niveaux = $niveaux->original;

    if ($BonID == 0) {

      $intervevants = di_ot_intervenant::where('ot_id', '=', $OtID)
        ->join('intervenants', function ($join) {
          $join->on('intervenants.IntervenantID', '=', 'di_ot_intervenants.intervenant_id');
        })->select('intervenants.*')->get();

      $bonintervenants = array();
      $now = Carbon::now();
      foreach ($intervevants as $intervenant) {
        //$bon_intervenant = new di_bon_intervenant;
        $bon_intervenant = new \stdClass();
        $bon_intervenant->BonIntervenantID = null;
        $bon_intervenant->bon_id = null;
        $bon_intervenant->intervenant_id = $intervenant['IntervenantID'];
        $bon_intervenant->name = $intervenant['name'];
        //$bon_intervenant->ot_intervenant_id = null;
        $bon_intervenant->date1 = $di_ot['datetime'];
        $bon_intervenant->time1 = $di_ot['datetime'];
        // $bon_intervenant->datetime1 = $di_ot['datetime'];
        $bon_intervenant->date2 = $di_ot['datetime'];
        $bon_intervenant->time2 = null;
        $bon_intervenant->tache_id = null;
        $bon_intervenant->tache = null;
        //$bon_intervenant->datetime2 = null;
        $bon_intervenant->description = '';
        //$bon_intervenant->note = null;
        //$bon_intervenant->exist = 1;

        array_push($bonintervenants, $bon_intervenant);
      }
    } else {

      $bonintervenants = di_bon_intervenant::where('bon_id', '=', $BonID)
        ->join('intervenants', function ($join) {
          $join->on('intervenants.IntervenantID', '=', 'di_bon_intervenants.intervenant_id');
        })
        ->join('equi_taches', function ($join) {
          $join->on('equi_taches.TacheID', '=', 'di_bon_intervenants.tache_id');
        })
        ->select(
          'di_bon_intervenants.BonIntervenantID',
          'di_bon_intervenants.bon_id',
          'di_bon_intervenants.intervenant_id',
          'intervenants.name',
          'di_bon_intervenants.date1',
          'di_bon_intervenants.datetime1 as time1',
          'di_bon_intervenants.date2',
          'di_bon_intervenants.datetime2 as time2',
          'di_bon_intervenants.tache_id',
          'equi_taches.tache',
          'di_bon_intervenants.description'
        )
        ->get();

      $di_bon = di_bon::where('BonID', '=', $BonID)
        ->join('users', function ($join) {
          $join->on('users.id', '=', 'di_bons.user_id');
        })
        ->leftjoin('users as modifieur', function ($join) {
          $join->on('modifieur.id', '=', 'di_bons.modifieur_user_id');
        })
        ->select('di_bons.*', 'users.name', 'modifieur.name as modifieur')
        ->first();
    }

    return response()->json(['ot' => $di_ot, 'bonintervenants' => $bonintervenants, 'niveaux' => $niveaux, 'bon' => $di_bon, 'isBonExist' => $isBonExist,'articles'=>$articles]); //,'bsms'=>$di_bsms,'bsos'=>$di_bsos]);

  }

    /*

BonIntervenantID: null
bon_id: null
date1: "2020-05-22 19:44:00"
date2: "2020-05-22 19:44:00"
description: null
intervenant_id: 1
name: "5addem1"
tache: null
tache_id: null
time1: "2020-05-22 19:44:00"
time2: null

BonIntervenantID	bon_id	intervenant_id	date1	time1	datetime1	date2	time2	datetime2	description	note	exist	created_at	updated_at

*/

  public function addBon(Request $request, $OtID)
  {
    

    $success=true;
    $OT_DATA=$this->OT_DATA($OtID);
    if( !( $OT_DATA->exist && $OT_DATA->statut=='enCours') ){
      $success=false;
      return response()->json(['success'=>$success,'bon'=>null]); 
    }
    
    $BonIntervenants = $request->BonIntervenants;
    $articles_r = $request->articles;
    $rapport = $request->Rapport;
    $DeletedBonIntervenantIDs = $request->DeletedBonIntervenantIDs;
    $UserID = JWTAuth::user()->id;

    
    // Partie 1 date time
    $di_bon = di_bon::create([
      'ot_id' => $OtID,
      'user_id' => $UserID,
      'rapport' => $rapport
    ]);
    $BonID = $di_bon->BonID;

    
    // changement de statut ot ferme 
    // changement de statut di ferme 
    $ot=di_ot::where('OtID','=',$OtID)->first();
    $DiID=$ot->di_id;
    di_ot::where('OtID','=',$OtID)->update(['statut'=>'ferme']);
    di_di::where('DiID','=',$DiID)->update(['statut'=>'ferme']);
    
    // suppression des bsms enAttente and bso ouvert
    $di_bsms=di_bsm::where('ot_id','=',$OtID)->where('statut','=','enAttente')->get();
    foreach($di_bsms as $di_bsm){
     $BsmID=$di_bsm['BsmID'];
     di_bsm_det::where('bsm_id','=',$BsmID)->delete();
    }
    di_bsm::where('ot_id','=',$OtID)->where('statut','=','enAttente')->delete();

    $di_bsos=di_bso::where('ot_id','=',$OtID)->where('isCorrectif','=',1)->where('statut','=','ouvert')->get(); // fil intithar 
    foreach($di_bsos as $di_bso){
     $BsoID=$di_bso['BsoID'];
     di_bso_det::where('bso_id','=',$BsoID)->delete();
    }
    di_bso::where('ot_id','=',$OtID)->where('isCorrectif','=',1)->where('statut','=','ouvert')->delete(); // fil intithar 
    
    
    



    // chouf bélik famma des autres changements 

    foreach ($BonIntervenants as $BonIntervenant) {

      $date1 = $this->DateFormYMD($BonIntervenant['date1']);
      $time1 = $this->TimeFormHM($BonIntervenant['time1']);
      $date2 = $this->DateFormYMD($BonIntervenant['date2']);
      $time2 = $this->TimeFormHM($BonIntervenant['time2']);

      $datetime1 = $this->combineDateTime($BonIntervenant['date1'], $BonIntervenant['time1']);
      $datetime2 = $this->combineDateTime($BonIntervenant['date2'], $BonIntervenant['time2']);

      di_bon_intervenant::create([
        'bon_id' => $BonID,
        'intervenant_id' => $BonIntervenant['intervenant_id'],
        'date1' => $date1,
        'time1' => $time1,
        'datetime1' => $datetime1,
        'date2' => $date2,
        'time2' => $time2,
        'datetime2' => $datetime2,
        'tache_id' => $BonIntervenant['tache_id'],
        'description' => $BonIntervenant['description'],
        'note' => 0
      ]);
    }
  
   
    $articles=Array();
    //$articles_r = $request->articles;
    $articles_bsm=$this->getUnionBsmsOt($OtID);
    foreach($articles_bsm as $article_bsm){
      $article_id_bsm=$article_bsm->article_id;
      $des=$article_bsm->des;
      $qted=$article_bsm->qted;
      $qtea=$article_bsm->qtea;
      foreach($articles_r as $article_r){
          $article_id_r=$article_r['article_id'];
          $qtear=$article_r['qtear'];
        if($article_id_bsm == $article_id_r){
          $value = new \stdClass();
          $value->article_id = $article_id_bsm;
          $value->des = $des;
          $value->qted = $qted;
          $value->qtea = $qtea;
          $value->qteu = $qtea - $qtear ;
          //$value->qtear = $article_r['qtear'] ; 
          //$value->qter = null;
          //$value->qtep = 0;
          array_push($articles,$value);
        }
      }
    }

    /*
    foreach($articles as $article){
      $di_bon_urp=di_bon_urp::create([
        'bon_id' => $BonID,
        'article_id' => $article->article_id,
        'qted' => $article->qted,
        'qtea' => $article->qtea,
        'qteu' => $article->qteu
      ]);
      if(!$di_bon_urp){$success=false;}
    }
    */

    // Partie 3 retour
    $isRetour=false;
    foreach($articles as $article){
     if($article->qtea > $article->qteu ){
        $isRetour=true;
     }
    }

   // if $isRetour == true : nasna3 array speciale béch najjim nesta3mil el Fonction LancerNouveauRetour
   $array_pour_LancerNouveauRetour=Array();
   if($isRetour){
    foreach($articles as $article){
      if($article->qtea > $article->qteu ){
         $value = new \stdClass();
         $value->article_id = $article->article_id;
         $value->qtear = $article->qtea - $article->qteu; // kenet > radditha - 
         array_push($array_pour_LancerNouveauRetour,$value);
      }
     }
     $RetourID=$this->LancerNouveauRetour($BonID,$array_pour_LancerNouveauRetour,$OtID);
     if(!$RetourID){$success=false;}
   }
   
   /*
   lazemni nthabbit mel fnction LancerNouveauRetour mchét mrigla w ba3id nfassa5 hétha 
   if($isRetour){
     $di_bon_retour=di_bon_retour::create([
      'bon_id' => $BonID
    ]);
    $RetourID=$di_bon_retour->RetourID;
   
   foreach($articles as $article){
    if($article->qtea > $article->qteu ){
      $di_bon_retour_det=di_bon_retour_det::create([
        'retour_id' => $RetourID,
        'article_id' => $article->article_id,
        'qtear' => $article->qtea - $article->qteu ,
        'qter' => null
      ]);      
    }
  }
}
*/

  
  //return response()->json($success);
  return response()->json(['success'=>$success,'bon'=>$di_bon]); 


  }






  public function editBon(Request $request, $BonID)
  {
    
    $success=true;

    $ot_id_di_bonp=di_bon::where('BonID','=',$BonID)->first();
    $ot_id=$ot_id_di_bonp->ot_id;

    $di_bsos=di_bso::where('ot_id','=',$ot_id)->where('isCorrectif','=',1)->where('statut','=','ouvert')->get();
    foreach($di_bsos as $di_bso){
     $BsoID=$di_bso['BsoID'];
     di_bso_det::where('bso_id','=',$BsoID)->delete();
     
    }
    di_bso::where('ot_id','=',$ot_id)->where('isCorrectif','=',1)->where('statut','=','ouvert')->delete();
    

    $BonIntervenants = $request->BonIntervenants;
    $articles_r = $request->articles;
    
    $rapport = $request->Rapport;
    $DeletedBonIntervenantIDs = $request->DeletedBonIntervenantIDs;
    $UserID = JWTAuth::user()->id;

    $now = Carbon::now();
    $di_bon_result=di_bon::where('BonID', '=', $BonID)->where('exist', '=', 1)->update([
      'rapport' => $rapport,
      'last_modif' => $now,
      'modifieur_user_id' => $UserID
    ]);
    di_bon::where('BonID', '=', $BonID)->where('exist', '=', 1)->increment('NbDeModif', 1);


    foreach ($BonIntervenants as $BonIntervenant) {

      $date1 = $this->DateFormYMD($BonIntervenant['date1']);
      $time1 = $this->TimeFormHM($BonIntervenant['time1']);
      $date2 = $this->DateFormYMD($BonIntervenant['date2']);
      $time2 = $this->TimeFormHM($BonIntervenant['time2']);
      $datetime1 = $this->combineDateTime($BonIntervenant['date1'], $BonIntervenant['time1']);
      $datetime2 = $this->combineDateTime($BonIntervenant['date2'], $BonIntervenant['time2']);

      if ($BonIntervenant['BonIntervenantID'] == null) {
        $di_bon_intervenant = di_bon_intervenant::create([
          'bon_id' => $BonID,
          'intervenant_id' => $BonIntervenant['intervenant_id'],
          'date1' => $date1,
          'time1' => $time1,
          'datetime1' => $datetime1,
          'date2' => $date2,
          'time2' => $time2,
          'datetime2' => $datetime2,
          'tache_id' => $BonIntervenant['tache_id'],
          'description' => $BonIntervenant['description'],
          'note' => 0
        ]);
        if (!$di_bon_intervenant) {
          $success = false;
        }
      }

      if ($BonIntervenant['BonIntervenantID'] > 0) {
        $di_bon_intervenant = di_bon_intervenant::where('BonIntervenantID', '=', $BonIntervenant['BonIntervenantID'])->update([
          'intervenant_id' => $BonIntervenant['intervenant_id'],
          'date1' => $date1,
          'time1' => $time1,
          'datetime1' => $datetime1,
          'date2' => $date2,
          'time2' => $time2,
          'datetime2' => $datetime2,
          'tache_id' => $BonIntervenant['tache_id'],
          'description' => $BonIntervenant['description'],
          'note' => 0
        ]);
      }
    }

    $arrIDsToDelete = explode(',', $DeletedBonIntervenantIDs, -1);
    foreach ($arrIDsToDelete as $ID) {
      $di_bon_intervenant = di_bon_intervenant::where('BonIntervenantID', '=', $ID)->delete();
      if (!$di_bon_intervenant) {
        $success = false;
      }
    }




    //-----------------------------------------------------------------
    // check if exist an new retour ou perdu utilise ----------------
    
    // tres importants , famma des condition logique 5ater ynajjim les valuers elli ymodiféhom ykounou méch logique .. 
    //la supression de tt retours enAttente ( méch directe yomkin ma na nsuprimich en cas de meme valuers walla en cas de ma famméch nv retour ama famma perduutilise)
    // bech ne5ou el valeur jdida mte3 retour
    // w ncomparéha b retour le9dima 
    // retour le9dima : totale des retours (qtear) mte3 les retours ferme
    // 1 kén retour jdida > retour le9dima donc ne7kiw 3la nouveau retour
    //   nv retour houwa diff entre rj - r9
    //   ba3d ma nasna3 nv retour nchouf famméch des modification fil tableeau urps
    
    // 2 kén retour jdida < retour le9dima 
    // sinaryouhét o5ra w 7ajét logique lazém ntestéha w nesta3mil des fonction ...
   

    // Etape1 :  verification esq exist nv retour ou nv perdu utilise
    // resultat deux array 
    $IsNouveauRetour=false;
    $IsNouveauRetourEgale=false;
    $IsNouveauRetourEgaleArray=Array();
    $articles_nv_retour=Array();
    $articles_nv_pu=Array();
    $ArrayArticlesTTRetour=$this->ReturnArrayArticlesTTRetourFerme($BonID);
    //return response()->json($ArrayArticlesTTRetour);

    foreach($articles_r as $article_new_r){
          $article_id_nr=$article_new_r['article_id'];
          $qtear_nr=$article_new_r['qtear'];

          // si retour article > 0 w ma sarlouch retour 9bal  
          if($qtear_nr>0 && !$this->IsArticleIDExistInArrayOfObject($article_id_nr,$ArrayArticlesTTRetour) ){
                $qtear_nv=$qtear_nr;
                $value = new \stdClass();
                $value->article_id = $article_id_nr;
                //$value->des = $des;
                $value->qtear = $qtear_nv;
                array_push($articles_nv_retour,$value);
          }

          // ma faméch 7aja kima hikka lél perdu utilise  ? 
          if($qtear_nr==0 && !$this->IsArticleIDExistInArrayOfObject($article_id_nr,$ArrayArticlesTTRetour) ){
            $IsNouveauRetour=true;
            $value = new \stdClass();
            $value->article_id = $article_id_nr;
            $value->zero=0;
            array_push($IsNouveauRetourEgaleArray,$value);
          }
          
          foreach($ArrayArticlesTTRetour as $article_ancien_r ){
            $article_id_ar=$article_ancien_r->article_id;
            $des=$article_ancien_r->des;
            $qtear_ar=$article_ancien_r->qtear;
            $qter_ar=$article_ancien_r->qter;
            if($article_id_nr == $article_id_ar){
              if($qtear_nr>$qtear_ar){
                // 3anna nouveau retour ! 
                $qtear_nv=$qtear_nr-$qtear_ar;
                $value = new \stdClass();
                $value->article_id = $article_id_nr;
                //$value->isIncrement = true;
                $value->qtear = $qtear_nv;
                array_push($articles_nv_retour,$value);
              }
              if($qtear_nr==$qtear_ar){

                 //  nv qtear = 0 
                 // ma3néha 3ibara ma famméch nv retour jdid 
                 // 5ater retour jdid 9ad retour elli deja retourne fil ferme : qtear fil ferme
                 // donc 3ibara kén l'article hétha mawjoud fil enAttente na99ast 9ad el enAttente elli inti 3amlou deja
                
                 $IsNouveauRetour=true;
                 $IsNouveauRetourEgale=true;
                 $value = new \stdClass();
                 $value->article_id = $article_id_nr;
                 $value->qtear_nr___nvqtear = $qtear_nr;
                 $value->qtear_ar___qtear_f = $qtear_ar;
                 array_push($IsNouveauRetourEgaleArray,$value);

              }
              if($qtear_nr<$qtear_ar){
                // kén article qtear_nr  mawjoud fil retour enAttente mahouch béch yetkapta lénna 5ater ya3mel fi comparaison m3a ArrayArticlesTTRetour ferme kahaw 
                // 3anna modification 
                // 3anna perdu utilse ...
                // tnajjim tkoun el if héthi zayda asl w net3adda lel ijtihéd el awwali

                // article_id TTqtear_e TTqtear_f TTqtear_ef TTqter [ TTqtep = TTqtear_f - TTqter ]
             // if ( qtear_nr < TTqter ) méch logique jémla 
             // if(qtear_nr - TTqtear_f < 0) = ( TTqtear_f - qtear_nr > 0 )  array_push(articles_nv_pu,) 
                // kiféch na3rif nb perdu utilise 9addéh 
                // nb = TTqtear_f - qtear_nr  

              if($qtear_nr<$qter_ar){ /* pas logique */ }
              // == ???????
              else{
                $qtepu=$qtear_ar - $qtear_nr;
                $value = new \stdClass();
                $value->article_id = $article_id_nr;
                $value->qtepu = $qtepu;
                array_push($articles_nv_pu,$value);
              }

              }
            }
          }


          // al ijtihed el awwali 
           
          // na3mil variable NouveauRetour = false; 
             // ken count($articles_nv_retour)>0 NouveauRetour=true;
             // Reponse awwaliya NouveauRetour=true; ( article walla pu w exist f retour enAttente) 

          // n3abbi nafs el array articles_nv_retour bil article elli ( qtear_nr no9sou ama ma wallewich pu ) 
          // n3abbi el array articles_nv_pu bil article elli ( pu ) 
          // nfare9 bin les articles elli no9sou el naw3in 
             // - naw3 1 : ( qtear_nr no9sou ama ma wallewich pu ) 
             // - naw3 2 : pu
          // kifech na3rafhom elli no9sou w ma walléwich pu:
             // médémni fi wost boucle mte3 articles_r 
             //-- theorique
             // 0. qtear_nv méch logique kén ykoun a9al men TTqter(f*)                            
             // 4. qtear_nv - TT qtear(f) < 0  wa9tha b tharoura nod5lou fil isArticlePu
             // v1=TT qtear(f) - qtear_nv [3ibara d5alna f tan9is m retour f]
             // v2=TT qtear(f) - TT qter(f) [NB TT perdu]
             //-- pratique
             // na3mil fonction jdida tejbedli les details kol d'un article féha 
                // article_id TTqtear_e TTqtear_f TTqtear_ef TTqter [ TTqtep = TTqtear_f - TTqter ]
             // if ( qtear_nr < TTqter ) méch logique jémla 
             // if(qtear_nr - TTqtear_f < 0) = ( TTqtear_f - qtear_nr > 0 )  array_push(articles_nv_pu,) 
                // kiféch na3rif nb perdu utilise 9addéh 
                // nb = TTqtear_f - qtear_nr  
                


          
          // tawwa nchouf 
                  //1 kén count(articles_nv_retour)   
                  //$RetourID=$this->LancerNouveauRetour($BonID,$articles_nv_retour);
                  
                  //2. kén count(articles_nv_pu) 
                       
          
          // Question 1 tsawwer modification féha article wa7id tbaddil , wel article hétha kén enAttente
          // w walla pu -> cha3malna donc f retour elli enAttente lazim yetna77a ménha el article hétha jémla
          // Reponse awwaliya : na3mil test kén famma article wa7id fi wost el array articles_nv_pu deja mawjoud fil enAttente
                               // NouveauRetour=true;
                               // lazim nlansi $RetourID=$this->LancerNouveauRetour($BonID,$articles_nv_retour);
                                // w ba3id ne5dem el 5edma mte3 pu ne5dem bil articles_nv_pu ma n3awedch nasna3ha mén jdid rod belik 

          // Question 2 : article na99ast fih w saretlou pu w perdu w c bon
          // ba3id ta3mil modification w tkabber fih lazim donc
          // méch lazim na3mil 7sebeti w nraj3ou perdu w nlanci retour enAttente ? kiféh ? 
          // ba3id n5ammém féha la7kaya m3a9da chwayya thaharli 
        
          
          

         



    }
    
    /*
    1  if(count($articles_nv_pu)>0)
      1.1 ynajjim ykoun famma pu kahaw
      1.2 w ynajjim ykoun famma modification mte3 retour enAttente kahaw
      1.3 w ynajjim ykoun famma zouz
    
    2 / if(count($articles_nv_retour)>0)
      b tharoura famma modification f retour enAttente kahaw

    
    donc béch na3mil testouwéti béch n5arréj de variable true or false elli houma
    
    // IsArticlePu : houwa caracterestique mte3 article méch globale kima IsModifRetour

                    //------------------------------------------------------------------------------------
                     // kiféch na3rif IsArticlePu 
                     0. qtear_nv méch logique kén ykoun a9al men TTqter(f*)
                     1.qtear_nv 1 w 3 w 5
                       béch ncherchi 3al article fil retour kol ferme w enAttente
                     2. TT qtear (ef)  // exmp : 7 ( 5 enAtt w 2 ferme )
                     3. v = TT qtear(ef) - qtear_nv    // exmp : v = 7 - 1  = 6
                                                   // exem : v = 7 - 3  = 4
                                                   // exem : v = 7 - 5  = 2
                   3.5. TT qtear(e) na3mil fonction tjibli valeur hétha walla fonction tjib les art kol                             
                     4. si v > TT qtear(e) wa9tha b tharoura nod5lou fil isArticlePu
                          v2=TT qtear(e)-v [3ibara d5alna f tan9is m retour f]
                          v3=TT qtear(f) - TT qter(f) [nb de TT perdu]
                          Pas Logique : v2 > v3 
                          Logique : v2 <= v3
                            w normalement na3mil verification mel front end 5ir
                            // donc front end fih 
                               qted qtea u qtear TTqter
                               qtear ? qtear ma tnajjamch tkoun a9al m TTqter
                               ma3néha el u ma ynajjamch ykoun akthér mén [ qtea - TTqter ] 

                        si v < TT qtear(e) lahné just IsModifRetour    //
                                                                     } generale : si v <=TT qtear(e)
                        si v == TT qtear(e) lahné just IsModifRetour   //



                        // **************************************

                         // kiféch na3rif IsArticlePu 
                     0. qtear_nv méch logique kén ykoun a9al men TTqter(f*)                            
                     4. qtear_nv - TT qtear(f) < 0  wa9tha b tharoura nod5lou fil isArticlePu
                          v1=TT qtear(f) - qtear_nv [3ibara d5alna f tan9is m retour f]
                          v2=TT qtear(f) - TT qter(f) [NB TT perdu]
                          Pas Logique : v1 > v2
                          Logique : v1 <= v2
                            w normalement na3mil verification mel front end 5ir
                            // donc front end fih 
                               qted qtea u qtear TTqter
                               qtear ? qtear ma tnajjamch tkoun a9al m TTqter
                               ma3néha el u ma ynajjamch ykoun akthér mén [ qtea - TTqter ]  
                        si qtear_nv - TT qtear(f) > 0 lahné just IsModifRetour    //
                                                                     } generale : qtear_nv - TT qtear(f) >= 0 
                        si qtear_nv - TT qtear(f) == 0  lahné just IsModifRetour   //

                    //#####################################################################################


                    
                     

                     // kiféch béch na3rif ennou tan9is f retour béch ybaddil just el retour enAttente
                        wa ella béch yod5il  Pu ? 
       
    // IsModifRetour : 
       1. fil cas thénya hawka wathe7 nouveau retour ma3néha IsModifRetour = true 
       2. fil cas loula kiféch na3réf IsModifRetour true or false ? 
          kol article n9os némchi nchoufou esque mawjoud f retour enAttente walla
          kén famma article wa7id mawjoud donc IsModifRetour = true 
          kén lé IsModifRetour = false
      
    // 
    

    if( IsModifRetour ){
      nfassa5 retour enAttente w nasna3 retour jdida
    }

    if(IsPu){ 
      
    }


    */



    // Etape 2
    // tester modif article tester les deux array et lancer un nv retour ou un nv perdu utilise si la longeur d'array > 0 
       // Tester si il ya un LancerNouveauRetour walla
       // 1. if si count($articles_nv_retour) > 0 
       // 2. else if exist un article dans articles_nv_pu exist dans un  retour enAttente 
    


    // Question : wa9téch tnajjim tkoun $articles_nv_retour false w IsAnyArticlePuExistInRetourEA true  ?
       // wa9t yabda mathalan 3andik article wa7id rajja3 mennou x w tha3et mennou 7aja 
       // w baddaltou walla pu , wa9tha articles_nv_retour == 0 false
       // Question : chma3néha ma3néha kén famma ayya article a5ir m3ah b tharoura articles_nv_retour true ?
      
       

      //return response()->json($articles_nv_pu);
      // return response()->json($articles_nv_retour);
      // return response()->json($IsNouveauRetour);
       

   
    if(count($articles_nv_retour)>0){$IsNouveauRetour=true;}
    else if($this->IsAnyArticlePuExistInRetourEA($BonID,$articles_nv_pu)){$IsNouveauRetour=true;}
  
       /*
       $res = new \stdClass();
       $res->articles_nv_retour = $articles_nv_retour;
       $res->articles_nv_pu = $articles_nv_pu;
       $res->IsNouveauRetour = $IsNouveauRetour;
       $res->IsNouveauRetourEgale = $IsNouveauRetourEgale;
       $res->IsNouveauRetourEgaleArray = $IsNouveauRetourEgaleArray;
       return response()->json($res);
       */
       
       
       if(count($articles_nv_pu)>0){
        // nchouf esque famma changement necessaire walla ? famma supprussion ou envoie de notification walla ...
        
        foreach($articles_nv_pu as $article_nv_pu){
          $article_id=$article_nv_pu->article_id;
          $qtepu=$article_nv_pu->qtepu;
         
          $ttlignesperdudunarticle=di_bon_retour::where('bon_id','=',$BonID)->where('statut','=','ferme')
          ->join('di_bon_retour_dets', function ($join) use($article_id) {
            $join->on('di_bon_retour_dets.retour_id', '=', 'di_bon_retours.RetourID')
            ->where('di_bon_retour_dets.article_id','=',$article_id)
            ->whereRaw('di_bon_retour_dets.qtear > di_bon_retour_dets.qter');
          })
          ->select('di_bon_retour_dets.*')
          ->orderByRaw('(di_bon_retour_dets.qtear - di_bon_retour_dets.qter) desc')
          ->get();
          
          $array_n3abyoha=Array();
          foreach($ttlignesperdudunarticle as $ligne){
            $qtedejap=$ligne['qtear']-$ligne['qter'];
          if($qtepu>0){ 
            $valuep = new \stdClass();
            $valuep->RetourDetID = $ligne['RetourDetID'];
            $valuep->retour_id = $ligne['retour_id'];
            $valuep->article_id = $article_id;

            if($qtepu>$qtedejap){ 
              $valuep->value = $qtedejap;
              array_push($array_n3abyoha,$valuep);
              $qtepu=$qtepu-$qtedejap;
           }else{ 
             $valuep->value = $qtepu;
             array_push($array_n3abyoha,$valuep);
             $qtepu=0;
           }

          }
          }

          $this->PerduUtilise($array_n3abyoha);
          if(count($array_n3abyoha)>0){$updating=true;}

          //return response()->json($array_n3abyoha);
        }
        
      }

      //return response()->json('test');


    if($IsNouveauRetour){
      // changement necessaire
         // supprimer les anciens notification et envoyer un nouveau notification de retour 
         $DeleteAllRetourNonFerme=$this->DeleteAllRetourNonFerme($BonID);
         $updating=true;
    }

    if(count($articles_nv_retour)>0){

      // lancer un nv retour
      $RetourID=$this->LancerNouveauRetour($BonID,$articles_nv_retour,$ot_id);
      $updating=true;
      /*
      // changement necessaire urps
        // nasna3 array speciale lel PlusMoinsQteu
        $array_PlusMoinsQteu=Array();
        foreach($articles_nv_retour as $article){
          $value = new \stdClass();
          $value->article_id = $article->article_id;
          $value->value = $article->qtear;
          array_push($array_PlusMoinsQteu,$value);
        }
        // nlanci el fonction de modification qteu fil urps
        $PlusMoinsQteu=$this->PlusMoinsQteu($BonID,'-',$array_PlusMoinsQteu);
        */

    }

    /*
    if($updating){
    $now = Carbon::now();
    di_bon::where('BonID', '=', $BonID)->where('exist', '=', 1)->update([
      'last_modif' => $now,
      'modifieur_user_id' => $UserID
    ]);
    di_bon::where('BonID', '=', $BonID)->where('exist', '=', 1)->increment('NbDeModif', 1);
    }
    */
    

    //return response()->json($RetourID);

    //##################################################################

    return response()->json(['success'=>$success,'success_edit'=>$di_bon_result]);


  }


  public function getBons(Request $request){
    
    $page = $request->input('page');
    $itemsPerPage = $request->input('itemsPerPage');
    $nodes = $request->input('nodes');


    $skipped = ($page - 1) * $itemsPerPage;
    $endItem = $skipped + $itemsPerPage;

    $filter = $request->input('filter');
    $datemin = $filter['datemin'];
    $datemax = $filter['datemax'];
    $datemin = app('App\Http\Controllers\Divers\generale')->ReglageDateMinDateMax($datemin, 'datemin');
    $datemax = app('App\Http\Controllers\Divers\generale')->ReglageDateMinDateMax($datemax, 'datemax');
    $searchFilterText = $filter['searchFilterText'];

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

    $bons = di_bon::whereDate('di_bons.created_at', '>=', $datemin)
      ->whereDate('di_bons.created_at', '<=', $datemax)
      ->join('users', function ($join) {
        $join->on('users.id', '=', 'di_bons.user_id');
      })
      ->join('di_bon_intervenants', function ($join) {
        $join->on('di_bon_intervenants.bon_id', '=', 'di_bons.BonID');
      })
      ->join('intervenants', function ($join) {
        $join->on('intervenants.IntervenantID', '=', 'di_bon_intervenants.intervenant_id');
      })
      
      // béch ncherci b esm el article mte3 bsm mte3 el bon
      ->join('di_ots', function ($join) {
        $join->on('di_ots.OtID', '=', 'di_bons.ot_id');
      })
      ->leftjoin('di_bsms', function ($join) {
        $join->on('di_bsms.ot_id', '=', 'di_ots.OtID');
      })
      ->leftjoin('di_bsm_dets', function ($join) {
        $join->on('di_bsm_dets.bsm_id', '=', 'di_bsms.BsmID');
      })
      ->leftjoin('articles', function ($join) {
        $join->on('articles.ArticleID', '=', 'di_bsm_dets.article_id');
      })

      ->join('di_dis', function ($join) {
        $join->on('di_dis.DiID', '=', 'di_ots.di_id');
      })
      ->join('equi_equipements', function ($join) use ($nodes) {
        if (count($nodes) == 0) {
          $join->on('equi_equipements.EquipementID', '=', 'di_dis.equipement_id');
        } else {
          $join->on('equi_equipements.EquipementID', '=', 'di_dis.equipement_id')
            ->whereIn('equi_equipements.EquipementID', $nodes);
        }
      })


      ->Where(function ($query) use ($searchFilterText) {
        return $query
          ->where('users.name', 'like', '%' . $searchFilterText . '%')
          ->orWhere('intervenants.name', 'like', '%' . $searchFilterText . '%')
          ->orWhere('articles.des', 'like', '%' . $searchFilterText . '%');
      })
      ->select('di_bons.*','users.name','equi_equipements.equipement')
      ->distinct('di_bons.BonID')
      ->orderBy('di_bons.created_at', 'desc');


    $countQuery = $bons->count();
    $bons = $bons->skip($skipped)->take($itemsPerPage)->get();

    return response()->json(['bons' => $bons, 'total' => $countQuery]);
  }


  public function combineDateTime($date, $time)
  {
    $date1 = $this->DateFormYMD($date);
    $time1 = $this->TimeFormHM($time);
    $date11 = new DateTime($date1);
    $time11 = new DateTime($time1);
    $datetime = new DateTime($date11->format('Y-m-d') . ' ' . $time11->format('H:i'));
    return $datetime;
  }

  public function ReturnArrayArticlesTTRetourFerme($BonID){
    $articles=Array();
    $di_bon_retours=di_bon_retour::where('bon_id','=',$BonID)->where('statut','=','ferme')->get();
    foreach ($di_bon_retours as $di_bon_retour) {
      $RetourID=$di_bon_retour['RetourID'];
      $di_bon_retour_dets=di_bon_retour_det::where('retour_id','=',$RetourID)
      ->join('articles', function ($join) {
        $join->on('articles.ArticleID', '=', 'di_bon_retour_dets.article_id');
      })->select('articles.des', 'di_bon_retour_dets.*')->get();
     
      foreach ($di_bon_retour_dets as $di_bon_retour_det) {
        $article_id = $di_bon_retour_det['article_id'];
        $des = $di_bon_retour_det['des'];
        $qtear = $di_bon_retour_det['qtear'];
        $qter = $di_bon_retour_det['qter'];

        if (isset($articles[$article_id])) {
          $value = (object) $articles[$article_id];
          $value->qtear += $qtear;
          $value->qter += $qter;
          $articles[$article_id] = $value;
        } else {
          $value = new \stdClass();
          $value->article_id = $article_id;
          $value->des = $des;
          $value->qtear = $qtear;
          $value->qter = $qter;
          $articles[$article_id] = $value;
        }
      }
    }
    $articles=$this->correctIArray($articles);
    return $articles;

   
  }

  function LancerNouveauRetour($BonID,$articles,$OtID){
    // chaque ligne de array articles doit contenir 
    // un objet contient les deux colones : article_id et qtear
    $success=true;
    $UserID = JWTAuth::user()->id;
    $di_bon_retour=di_bon_retour::create([
      'bon_id' => $BonID,
      'ot_id'=>$OtID,
      'user_id'=>$UserID
    ]);
    
    if(!$di_bon_retour){$success=false;}
    $RetourID=$di_bon_retour->RetourID;
   
   foreach($articles as $article){
      if($article->qtear>0){
      $di_bon_retour_det=di_bon_retour_det::create([
        'retour_id' => $RetourID,
        'article_id' => $article->article_id,
        'qtear' => $article->qtear,
        'qter' => null
      ]); 

      if(!$di_bon_retour_det){$success=false;}
      }
  }
  if($success){return $RetourID;}else{return false;}
  }

  function PlusMoinsQteu($BonID,$signe,$array){
    $success=true;
    if($signe=='+'){$l_signe=1;}else{$l_signe=-1;}
    foreach($array as $article){
       $article_id=$article->article_id;
       $value=$article->value;
       $di_bon_urp=di_bon_urp::where('bon_id','=',$BonID)->where('article_id','=',$article_id)->increment('qteu',$l_signe*$value); 
       
       //error::insert(['error'=>'BonID:'.$BonID.',article_id:'.$article_id.',signe:'.$signe.',l_signe:'.$l_signe.',value:'.$value]);


       if(!$di_bon_urp){$success=false;}
    }
    return $success;
  }


  public function IsArticleIDExistInArrayOfObject($ArticleID,$array){
    $exist=false;
    foreach($array as $obj){
      $article_id=$obj->article_id;
      if($article_id == $ArticleID){ $exist=true;}
    }
    return $exist;
  }


  public function getBonForAff($id){
    
    $bon=di_bon::where('BonID','=',$id)
    ->leftjoin('users as modifieur', function ($join) {
      $join->on('modifieur.id', '=', 'di_bons.modifieur_user_id');
    })
    ->join('users', function ($join) {
      $join->on('users.id', '=', 'di_bons.user_id');
    })
    ->select('di_bons.*','modifieur.name as modifieur','users.name')
    ->first();

    $taches = di_bon_intervenant::where('bon_id', '=', $id)
        ->join('intervenants', function ($join) {
          $join->on('intervenants.IntervenantID', '=', 'di_bon_intervenants.intervenant_id');
        })
        ->join('equi_taches', function ($join) {
          $join->on('equi_taches.TacheID', '=', 'di_bon_intervenants.tache_id');
        })
        ->select(
          'di_bon_intervenants.BonIntervenantID',
          'di_bon_intervenants.bon_id',
          'di_bon_intervenants.intervenant_id',
          'intervenants.name',
          'di_bon_intervenants.date1',
          'di_bon_intervenants.datetime1 as time1',
          'di_bon_intervenants.date2',
          'di_bon_intervenants.datetime2 as time2',
          'di_bon_intervenants.tache_id',
          'equi_taches.tache',
          'di_bon_intervenants.description'
        )
        ->get();
    

    $retours=$this->getCompleteBonDetRetours($id);
    
    $UserID = JWTAuth::user()->id;
    $me = app('App\Http\Controllers\Divers\generale')->getUserPosts($UserID);
    $me = $me->original;
    $me = $me[0];
   // $posts = $me->posts;

/*
    if ($BonID>0) {
    
      foreach($articles as $article){
      
          $article->qteu= $article->qtea - $this->getTTqtear_efTTqter_f($BonID,$article->article_id)->TTqtear_ef;
          $article->qter= $this->getTTqtear_efTTqter_f($BonID,$article->article_id)->TTqter_f;
      }

    }
*/

    return response()->json(['bon'=>$bon,'taches'=>$taches,'retours'=>$retours,'me'=>$me]);

  }


  
  public function getBonForAff2($id){
    
    $bonp = di_bon::where('BonID', '=', $id)
    ->join('users', function ($join) {
      $join->on('users.id', '=', 'di_bons.user_id');
    })
    ->leftjoin('users as modifieur', function ($join) {
      $join->on('modifieur.id', '=', 'di_bons.modifieur_user_id');
    })
    ->select(
      'di_bons.*',
      'users.name',
      'modifieur.name as modifieur_name'
    )
    ->first();


    $prev_otp = di_ot::where('OtID', '=', $bonp->ot_id)
    
    ->join('users', function ($join) {
      $join->on('users.id', '=', 'di_ots.user_id');
    })
    /*
    ->leftjoin('users as modifieur', function ($join) {
      $join->on('modifieur.id', '=', 'prev_otps.modifieur_user_id');
    })
    */
    
    ->leftjoin('equi_taches', function ($join) {
      $join->on('equi_taches.TacheID', '=', 'di_ots.tache_id');
    })

    ->join('di_dis', function ($join) {
      $join->on('di_dis.DiID', '=', 'di_ots.di_id');
    })
    ->leftjoin('equi_anomalies', function ($join) {
      $join->on('equi_anomalies.AnomalieID', '=', 'di_dis.anomalie_id');
    })

    ->select(
      'di_ots.*',
      'users.name',
      //'modifieur.name as modifieur_name',
      'equi_taches.tache',
      'equi_anomalies.anomalie',
      'di_dis.equipement_id'
    )
    ->first();

  $EquipementID = $prev_otp->equipement_id;
  $niveaux = app('App\Http\Controllers\Equipement\equipement')->getNiveausCompletDetByEquipementID($EquipementID);
  $niveaux = $niveaux->original;
  
  $bonpintervenants = di_bon_intervenant::where('bon_id', '=', $id)
        ->join('intervenants', function ($join) {
          $join->on('intervenants.IntervenantID', '=', 'di_bon_intervenants.intervenant_id');
        })
        ->join('equi_taches', function ($join) {
          $join->on('equi_taches.TacheID', '=', 'di_bon_intervenants.tache_id');
        })
        ->select(
          'di_bon_intervenants.BonIntervenantID',
          'di_bon_intervenants.bon_id',
          'di_bon_intervenants.intervenant_id',
          'intervenants.name',
          'di_bon_intervenants.date1',
          'di_bon_intervenants.datetime1 as time1',
          'di_bon_intervenants.date2',
          'di_bon_intervenants.datetime2 as time2',
          'di_bon_intervenants.tache_id',
          'equi_taches.tache',
          'di_bon_intervenants.description'
        )
        ->get();


    //$articles=Array();
    $articles=$this->getUnionBsmsOt($bonp->ot_id);
      foreach($articles as $article){
          $article->qteu= $article->qtea - $this->getTTqtear_efTTqter_f($id,$article->article_id)->TTqtear_ef;
          $article->qter= $this->getTTqtear_efTTqter_f($id,$article->article_id)->TTqter_f;
      }

    //$pdrs=$this->getUnionBsoOtp($bonp->otp_id);

    $bsos=$this->GetUnionAllBsosByOtID($bonp->ot_id);
    

  return response()->json(['bon' => $bonp,'niveaux'=>$niveaux,'ot'=>$prev_otp,'intervenants'=>$bonpintervenants,'articles'=>$articles,'bsos'=>$bsos]);
  
  }

  public function GetUnionAllBsosByOtID($OtID){

    $array_bsos=array();
    $bsos = di_bso::where('ot_id', '=', $OtID)->where('isCorrectif','=',0)->where('exist','=',1)->get();

    foreach ($bsos as $bso){
    $BsoID=$bso['BsoID'];
    $req = new Request();
    $req->merge(['Src' =>'correctif']); 
    $BsoWithResponse = app('App\Http\Controllers\Correctif\correctif')->getBsoWithResponse($req,$BsoID);
    $BsoWithResponse = $BsoWithResponse->original;
    array_push($array_bsos,$BsoWithResponse);
    }
    
    //return response()->json(['bsos'=>$array_bsos]);
    return $array_bsos;

  }




  
  public function getCompleteBonDetRetours($BonID)
  {
    $bon=di_bon::where('BonID','=',$BonID)->first();
    $OtID=$bon->ot_id;
    
    $articles = array();
    $bsms = di_bsm::where('ot_id', '=', $OtID)->where('statut', '=', 'accepted')->where('exist', '=', 1)->get();

    foreach ($bsms as $bsm) {

      $BsmID = $bsm['BsmID'];

      $di_response_bsm = di_response_bsm::where('bsm_id', '=', $BsmID)->where('exist', '=', 1)->first();
      $ResponseBsmID = $di_response_bsm->ResponseBsmID;
      $di_response_bsm_dets = di_response_bsm_det::where('response_bsm_id', '=', $ResponseBsmID)
        ->join('di_bsm_dets', function ($join) {
          $join->on('di_bsm_dets.BsmDetID', '=', 'di_response_bsm_dets.bsm_det_id');
        })
        ->join('articles', function ($join) {
          $join->on('articles.ArticleID', '=', 'di_bsm_dets.article_id');
        })
        ->select('articles.des', 'di_bsm_dets.article_id','di_bsm_dets.qte as qted','di_response_bsm_dets.qte as qtea')
        ->get();

      foreach ($di_response_bsm_dets as $di_response_bsm_det) {
        $article_id = $di_response_bsm_det['article_id'];
        $des = $di_response_bsm_det['des'];
        $qted = $di_response_bsm_det['qted'];
        $qtea = $di_response_bsm_det['qtea'];

        if (isset($articles[$article_id])) {
          $value = (object) $articles[$article_id];
          $value->qted += $qted;
          $value->qtea += $qtea;
          //$value->qteu += $qtea;//?
          $articles[$article_id] = $value;
        } else {
          $value = new \stdClass();
          $value->article_id = $article_id;
          $value->des = $des;
          $value->qted = $qted;
          $value->qtea = $qtea;
          $value->qteu = 0; //$qtea;
          $value->qtear_e = 0;
          $value->qtear_f = 0;
          $value->qter = 0;
          $value->qtep = 0;
          $articles[$article_id] = $value;
        }
      }
    }
    $articles=$this->correctIArray($articles);

    foreach($articles as $article){
      $qtea=$article->qtea;
      $qtear_e=$this->getTTqtear_efTTqter_f($BonID,$article->article_id)->TTqtear_e;
      $qtear_f=$this->getTTqtear_efTTqter_f($BonID,$article->article_id)->TTqtear_ef - $qtear_e;
      $qter=$this->getTTqtear_efTTqter_f($BonID,$article->article_id)->TTqter_f;
      $qteu=$qtea - $qtear_e - $qtear_f ; 
      $qtep=$qtear_f - $qter;

      $article->qteu = $qteu; 
      $article->qtear_e = $qtear_e;
      $article->qtear_f = $qtear_f;
      $article->qter = $qter;
      $article->qtep = $qtep;

    }

    return $articles;
  }

  public function DeleteRetourByRetourID($RetourID){
   $success=true;
   $di_bon_retour_dets=di_bon_retour_det::where('retour_id','=',$RetourID)->get();
   di_bon_retour_det::where('retour_id','=',$RetourID)->delete();
   $di_bon_retour=di_bon_retour::where('RetourID','=',$RetourID)->first();
   $BonID=$di_bon_retour->bon_id;
   $di_bon_retour_delete=di_bon_retour::where('RetourID','=',$RetourID)->delete();
   if(!$di_bon_retour_dets || !$di_bon_retour || !$di_bon_retour_delete ){$success=false;}
   
   /*
   // nasna3 array speciale lel PlusMoinsQteu
   $array_PlusMoinsQteu=Array();  
   foreach($di_bon_retour_dets as $article){
    $value = new \stdClass();
    $value->article_id = $article->article_id;
    $value->value = $article->qtear;
    array_push($array_PlusMoinsQteu,$value);
   }
   $PlusMoinsQteu=$this->PlusMoinsQteu($BonID,'+',$array_PlusMoinsQteu);
   if(!$PlusMoinsQteu){$success=false;}
   */

   return $success;
  }

  public function DeleteAllRetourNonFerme($BonID){
    $success=true;
    $di_bon_retours=di_bon_retour::where('bon_id','=',$BonID)->where('statut','=','enAttente')->get();
    foreach($di_bon_retours as $di_bon_retour){
      $RetourID=$di_bon_retour['RetourID'];
      $DeleteRetourByRetourID = $this->DeleteRetourByRetourID($RetourID);
      if(!$DeleteRetourByRetourID){ $success=false;}
    }
    return $success;
  }

  public function IsAnyArticlePuExistInRetourEA($BonID,$array){
    $di_bon_retours=di_bon_retour::where('bon_id','=',$BonID)->where('statut','=','enAttente')->get();
    foreach($di_bon_retours as $di_bon_retour){
      $RetourID=$di_bon_retour['RetourID'];
      $di_bon_retour_dets=di_bon_retour_det::where('retour_id','=',$RetourID)->get();
      foreach($di_bon_retour_dets as $di_bon_retour_det){
        $article_id_det=$di_bon_retour_det['article_id'];
        foreach($array as $article){
          $article_id_arr=$article->article_id;
          if($article_id_arr == $article_id_det ){
            return true;
          }
        }
      }

    }
    return false;
    
  }


  public function getTTqtear_efTTqter_f($BonID,$article_id){
    // qteu = TTqtea - 
    // TTqtear_ef
    // qter = TTqter_f
    

    $TTqter_f=0;
    $di_bon_retours=di_bon_retour::where('bon_id','=',$BonID)->where('statut','=','ferme')->get();
    foreach ($di_bon_retours as $di_bon_retour) {
      $RetourID=$di_bon_retour['RetourID'];
      $di_bon_retour_dets=di_bon_retour_det::where('retour_id','=',$RetourID)->where('article_id','=',$article_id)->get();
      foreach ($di_bon_retour_dets as $di_bon_retour_det) {
        $TTqter_f+=$di_bon_retour_det['qter'];
      }
    }

    $TTqtear_ef=0;
    $di_bon_retours=di_bon_retour::where('bon_id','=',$BonID)->get();
    foreach ($di_bon_retours as $di_bon_retour) {
      $RetourID=$di_bon_retour['RetourID'];
      $di_bon_retour_dets=di_bon_retour_det::where('retour_id','=',$RetourID)->where('article_id','=',$article_id)->get();                        
      foreach ($di_bon_retour_dets as $di_bon_retour_det) {
        $TTqtear_ef+=$di_bon_retour_det['qtear'];
      }
    }

    $TTqtear_e=0;
    $di_bon_retours=di_bon_retour::where('bon_id','=',$BonID)->where('statut','=','enAttente')->get();
    foreach ($di_bon_retours as $di_bon_retour) {
      $RetourID=$di_bon_retour['RetourID'];
      $di_bon_retour_dets=di_bon_retour_det::where('retour_id','=',$RetourID)->where('article_id','=',$article_id)->get();                        
      foreach ($di_bon_retour_dets as $di_bon_retour_det) {
        $TTqtear_e+=$di_bon_retour_det['qtear'];
      }
    }


    $value = new \stdClass();
    $value->TTqter_f = $TTqter_f;
    $value->TTqtear_ef = $TTqtear_ef;
    $value->TTqtear_e = $TTqtear_e;
    
    return $value;

  }


  public function PerduUtilise($array){
    $UserID = JWTAuth::user()->id;
    foreach($array as $ligne){
      $RetourDetID=$ligne->RetourDetID;
      $retour_id=$ligne->retour_id;
      $article_id=$ligne->article_id;
      $value=$ligne->value;
      di_bon_retour_det::where('RetourDetID','=',$RetourDetID)->increment('qtear',(-1*$value));
      //$di_bon_retour_det=di_bon_retour_det::where('RetourDetID','=',$RetourDetID)->
      // notification
      // tableau perdu generale increment 
      
      $retour_perdu=app('App\Http\Controllers\Divers\generale')->UpdatePerduByArticleID($article_id,(-1*$value));

      di_bon_retour_hist::create([
        'retour_id'=>$retour_id,
        'article_id'=>$article_id,
        'user_id'=>$UserID,
        'isModification'=>false,
        'type'=>'pu',
        'value'=>$value
      ]);
    }
  }





  public function getEventsPlans(Request $request){

    $datestart=$request->input('datestart');
    $dateend=$request->input('dateend');

    /*
    $arr=Array();
    $di = new \stdClass();
    $di->id = 1;
    $di->anomalie = "Anomalie1";
    $di->release_date = "2020-06-11 01:10:00";
    array_push($arr,$di);
    return response()->json($arr);
    */

    $di_di_plans=di_di_plan::where('isExecuted','=',0)
      ->whereDate('di_di_plans.datetime', '>=', $datestart)
      ->whereDate('di_di_plans.datetime', '<=', $dateend)
      ->join('di_dis', function ($join) {
        $join->on('di_dis.DiID', '=', 'di_di_plans.di_id');
      })
      ->join('equi_anomalies', function ($join) {
        $join->on('equi_anomalies.AnomalieID', '=', 'di_dis.anomalie_id');
      })
      ->select('di_di_plans.*','equi_anomalies.anomalie')
      ->get();
      return response()->json($di_di_plans);


  }






  }




