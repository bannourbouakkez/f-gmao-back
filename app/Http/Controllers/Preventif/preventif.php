<?php

namespace App\Http\Controllers\Preventif;




use App\Model\Correctif\di_bso;
use App\Model\Correctif\di_bso_det;

use App\Model\Preventif\prev_intervention;
use App\Model\Preventif\prev_otp;
use App\Model\Preventif\prev_bonp;
use App\Model\Preventif\prev_bonp_intervenant;

use App\Model\Preventif\prev_bsm;
use App\Model\Preventif\prev_bsm_det;
use App\Model\Preventif\prev_response_bsm;
use App\Model\Preventif\prev_response_bsm_det;

use App\Model\Preventif\prev_bon_retour;
use App\Model\Preventif\prev_bon_retour_det;
use App\Model\Preventif\prev_bon_retour_hist;

use App\Model\Preventif\prev_reservation;
use App\Model\Preventif\prev_reservation_det;

use App\Model\Preventif\prev_reservedintervenant;
use App\Model\Preventif\prev_reservedintervenant_det;


// just for test
use App\Model\Correctif\di_bon_intervenant;





use Illuminate\Support\Facades\DB;
use App\Model\Divers\error;
use App\User;
use Carbon\Carbon;
use DateTime;
use Dotenv\Regex\Success;
use JWTFactory;
use JWTAuth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class preventif extends Controller
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

  public function combineDateTime($date, $time)
  {
    $date1 = $this->DateFormYMD($date);
    $time1 = $this->TimeFormHM($time);
    $date11 = new DateTime($date1);
    $time11 = new DateTime($time1);
    $datetime = new DateTime($date11->format('Y-m-d') . ' ' . $time11->format('H:i'));
    return $datetime;
  }



  public function addIntervention(Request $request){
  
    $success=true;
    $msg="L'intervention est ajoutée avec succées .";
    $form = $request->input('form');
    $UserID = JWTAuth::user()->id;
    $form['user_id'] = $UserID;
    $form['ddr']=$this->DateFormYMD($form['ddr']);
    //$form['decalage'] = 0;

    if($form['frequence']==0){
      $form['date_resultat'] =Carbon::parse($this->DateFormYMD($form['date_resultat']));
    }

    if($form['frequence']>0){
      $form['date_resultat'] =Carbon::parse($form['ddr'])->addDays($form['frequence']*7); 
    }
    
    //---------------$form['ancien_ddr']=Carbon::parse($form['ddr']);
    $form['ancien_ddr']=NULL;
    
    $prev_intervention=null;
    if(!$this->InterventionIsCoupleEquipementTacheExist($form['equipement_id'],$form['tache_id'])){
    $prev_intervention=prev_intervention::create($form);
    }else{
      $success=false;
      $msg="Error : il existe déja une intervention avec la memme equipement et la meme tache . ";
    }
    
    // fonction generale automatique 

    return response()->json(['success'=>$success,'intervention'=>$prev_intervention,'msg'=>$msg]);
  }

  public function editIntervention(Request $request,$id){
  
    $msg='';
    $success=true;

    if(!$this->isExistOtpEnCoursOfThisInterventionID($id)){

    $form = $request->input('form');
    $UserID = JWTAuth::user()->id;
    $form['user_id'] = $UserID;
    $form['ddr']=$this->DateFormYMD($form['ddr']);
    $form['decalage'] = 0;

    if($form['frequence']==0){
      $form['date_resultat'] =Carbon::parse($this->DateFormYMD($form['date_resultat']));
    }

    if($form['frequence']>0){
      $form['date_resultat'] =Carbon::parse($form['ddr'])->addDays($form['frequence']*7); 
    }

    
    //-----------------------$form['ancien_ddr']=Carbon::parse($this->DateFormYMD($form['ddr']));
    $form['ancien_ddr']=NULL;
    
    $now = Carbon::now();
    $form['last_modif'] = $now;
    $form['modifieur_user_id'] = $UserID;
    prev_intervention::where('InterventionID','=',$id)->increment('NbDeModif', 1);
    

    $prev_intervention=prev_intervention::where('InterventionID','=',$id)->update($form);

    
    if($form['parametrage']=='planification'){
      $this->SetIsPlanified($id,true); // zayda 
      $this->syestemePlans();
    }
   
 
  }else{
    $success=false;
    $msg='Vous pouvez pas modifiée cette intrevention maintenant car elle est reliée avec un OT déja en cours .';
    $prev_intervention=null;
  }

    return response()->json(['success'=>$success,'intervention'=>$prev_intervention,'msg'=>$msg]);
    
  }

  public function getInterventions(Request $request){

    $page = $request->input('page');
    $itemsPerPage = $request->input('itemsPerPage');
    $nodes = $request->input('nodes');

    $skipped = ($page - 1) * $itemsPerPage;
    $endItem = $skipped + $itemsPerPage;

    $filter = $request->input('filter');
    //$datemin = $filter['datemin'];
    //$datemax = $filter['datemax'];
    //$datemin = app('App\Http\Controllers\Divers\generale')->ReglageDateMinDateMax($datemin, 'datemin');
    //$datemax = app('App\Http\Controllers\Divers\generale')->ReglageDateMinDateMax($datemax, 'datemax');
    $searchFilterText = $filter['searchFilterText'];

    $type = $filter['type'];
    $parametrage = $filter['parametrage'];

    $UserID = JWTAuth::user()->id;
    $me = app('App\Http\Controllers\Divers\generale')->getUserPosts($UserID);
    $me = $me->original;
    $me = $me[0];
    $posts = $me->posts;

    $isAdmin = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, ['Admin']);
    $isMethode = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, ['Methode']);
    $OthersAuthorized = ['ChefDePoste', 'ResponsableMaintenance'];
    $isOthersAuthorized = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, $OthersAuthorized);

    $plans = prev_intervention::
         Where('prev_interventions.exist','=',1)
        //whereDate('prev_interventions.date_resultat', '>=', $datemin)
      //->whereDate('prev_interventions.date_resultat', '<=', $datemax)
      ->whereIn('prev_interventions.type', $type)
      ->whereIn('prev_interventions.parametrage', $parametrage)
      //->where('prev_interventions.isPlanified','=',1)
      
      /*
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
      */

      ->join('users', function ($join) {
        $join->on('users.id', '=', 'prev_interventions.user_id');
      })
      ->join('equi_equipements', function ($join) use ($nodes) {
        if (count($nodes) == 0) {
          $join->on('equi_equipements.EquipementID', '=', 'prev_interventions.equipement_id');
        } else {
          $join->on('equi_equipements.EquipementID', '=', 'prev_interventions.equipement_id')
            ->whereIn('equi_equipements.EquipementID', $nodes);
        }
      })
      ->leftjoin('equi_taches', function ($join) {
        $join->on('equi_taches.TacheID', '=', 'prev_interventions.tache_id');
      })
      ->Where(function ($query) use ($searchFilterText) {
        return $query
          ->where('equi_taches.tache', 'like', '%' . $searchFilterText . '%')
          ->orWhere('prev_interventions.type', 'like', '%' . $searchFilterText . '%')
          ->orWhere('prev_interventions.parametrage', 'like', '%' . $searchFilterText . '%')
          ->orWhere('equi_equipements.equipement', 'like', '%' . $searchFilterText . '%');
          //->orWhere('users.name', 'like', '%' . $searchFilterText . '%');
      })
      ->select('prev_interventions.*','equi_taches.tache','users.name','equi_equipements.equipement')
      ->orderBy('prev_interventions.created_at', 'asc');



    $countQuery = $plans->count();
    $plans = $plans->skip($skipped)->take($itemsPerPage)->get();

    return response()->json(['interventions' => $plans, 'me' => $me, 'total' => $countQuery]);


  }

  public function deleteIntervention($id){
     $prev_intervention=prev_intervention::where('InterventionID','=',$id)->update(['exist'=>false]);
     if($prev_intervention){
       return response()->json(['success'=>true,'result'=>$prev_intervention]);
     }else{
      return response()->json(['success'=>false,'result'=>$prev_intervention]);
     }
     
  }


  public function decalageIntervention(Request $request,$id){
  
    $form = $request->input('form');
    $end=Carbon::parse($form['new_date_resultat']); //->addDays($form['frequence']*7);
    $prev_intervention=prev_intervention::where('InterventionID','=',$id)->first();
    $start=Carbon::parse($prev_intervention->date_resultat);
    $decalage=$start->diff($end)->days;

    $prev_intervention=prev_intervention::where('InterventionID','=',$id)->update([
      'decalage'=>$decalage,
      'date_resultat'=>$end
    ]);
 
    return response()->json($prev_intervention);
    
  }


  public function getIntervention($id){
   $prev_intervention=prev_intervention::where('InterventionID','=',$id)
   ->join('equi_equipements', function ($join) {
    $join->on('equi_equipements.EquipementID', '=', 'prev_interventions.equipement_id');
   })
   ->join('equi_taches', function ($join) {
    $join->on('equi_taches.TacheID', '=', 'prev_interventions.tache_id');
  })
  ->join('users', function ($join) {
    $join->on('users.id', '=', 'prev_interventions.user_id');
  })
  ->select('prev_interventions.*','equi_taches.tache','users.name','equi_equipements.equipement')
  ->first();

   $equipement = prev_intervention::where('InterventionID','=',$id)
      ->join('equi_equipements', function ($join) {
        $join->on('equi_equipements.EquipementID', '=', 'prev_interventions.equipement_id');
      })
      ->select('equi_equipements.EquipementID','equi_equipements.equipement', 'equi_equipements.Niv')
      ->first();
  
      $EquipementID = $prev_intervention->equipement_id;
      $niveaux = app('App\Http\Controllers\Equipement\equipement')->getNiveausCompletDetByEquipementID($EquipementID);
      $niveaux = $niveaux->original;

  
      $isModifiable=!$this->isExistOtpEnCoursOfThisInterventionID($id);

   return response()->json(['intervention'=>$prev_intervention,'equipement'=>$equipement,'niveaux'=>$niveaux,'isModifiable'=>$isModifiable]);
  
  }


  public function getPlans(Request $request){
    
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

    $UserID = JWTAuth::user()->id;
    $me = app('App\Http\Controllers\Divers\generale')->getUserPosts($UserID);
    $me = $me->original;
    $me = $me[0];
    $posts = $me->posts;

    $isAdmin = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, ['Admin']);
    $isMethode = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, ['Methode']);
    $OthersAuthorized = ['ChefDePoste', 'ResponsableMaintenance'];
    $isOthersAuthorized = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, $OthersAuthorized);

    $plans = prev_intervention::
        where('prev_interventions.exist','=',true)
      ->whereDate('prev_interventions.date_resultat', '>=', $datemin)
      ->whereDate('prev_interventions.date_resultat', '<=', $datemax)
      ->whereIn('prev_interventions.type', $type)
      ->where('prev_interventions.isPlanified','=',1)
      
      /*
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
      */

      ->join('users', function ($join) {
        $join->on('users.id', '=', 'prev_interventions.user_id');
      })
      ->join('equi_equipements', function ($join) use ($nodes) {
        if (count($nodes) == 0) {
          $join->on('equi_equipements.EquipementID', '=', 'prev_interventions.equipement_id');
        } else {
          $join->on('equi_equipements.EquipementID', '=', 'prev_interventions.equipement_id')
            ->whereIn('equi_equipements.EquipementID', $nodes);
        }
      })
      ->leftjoin('equi_taches', function ($join) {
        $join->on('equi_taches.TacheID', '=', 'prev_interventions.tache_id');
      })
      ->Where(function ($query) use ($searchFilterText) {
        return $query
          ->where('equi_taches.tache', 'like', '%' . $searchFilterText . '%')
          ->orWhere('prev_interventions.type', 'like', '%' . $searchFilterText . '%')
          ->orWhere('equi_equipements.equipement', 'like', '%' . $searchFilterText . '%');
          //->orWhere('users.name', 'like', '%' . $searchFilterText . '%');
      })
      ->select('prev_interventions.*','equi_taches.tache','users.name','equi_equipements.equipement')
      ->orderBy('prev_interventions.date_resultat', 'asc');



    $countQuery = $plans->count();
    $plans = $plans->skip($skipped)->take($itemsPerPage)->get();

    return response()->json(['plans' => $plans, 'me' => $me, 'total' => $countQuery]);

  }

  public function getOtp(Request $request){

    $OtpID = $request->input('OtpID');
    $InterventionID = $request->input('InterventionID');
    
    if($OtpID==0){
      $otp=null;
    }else{

      $otp=prev_otp::where('OtpID','=',$OtpID)
      
      ->leftjoin('prev_bonps', function ($join) {
        $join->on('prev_bonps.otp_id', '=', 'prev_otps.OtpID');
      })
      ->join('users', function ($join) {
        $join->on('users.id', '=', 'prev_otps.user_id');
      })
      ->leftjoin('users as modifieur', function ($join) {
        $join->on('modifieur.id', '=', 'prev_otps.modifieur_user_id');
      })
      


      /*
      ->join('prev_interventions', function ($join) {
        $join->on('prev_interventions.InterventionID', '=', 'prev_otps.intervention_id');
      })
      ->leftjoin('equi_taches', function ($join) {
        $join->on('equi_taches.TacheID', '=', 'prev_interventions.tache_id');
      })
      */
      
      ->select('prev_otps.*','prev_bonps.BonpID','users.name',
      'modifieur.name as modifieur_name') // ,'equi_taches.tache')
      
      ->first();

      $intervention_id=$otp->intervention_id;
      $InterventionID=$intervention_id;
     
    
    }

    $intervention=prev_intervention::where('InterventionID','=',$InterventionID) //->where('prev_interventions.exist','=',true)
    ->join('users', function ($join) {
      $join->on('users.id', '=', 'prev_interventions.user_id');
    })
    ->join('equi_equipements', function ($join) {
      $join->on('equi_equipements.EquipementID', '=', 'prev_interventions.equipement_id');
    })
    ->join('equi_taches', function ($join) {
      $join->on('equi_taches.TacheID', '=', 'prev_interventions.tache_id');
    })
    ->select('prev_interventions.*','equi_taches.tache','equi_equipements.equipement','users.name','equi_equipements.Niv')
    ->first();
    

    // Partie reservation __________________

    $reservation= prev_reservation::where('otp_id','=',$OtpID)->first();
    if($reservation){
    $ReservationID=$reservation->ReservationID;
    $req = new Request();
    $articles_reserved=$this->getReservationBsmByID($req,$ReservationID);
    $articles_reserved=$articles_reserved->original;
    }else{
      $reservation=null;
      $retour = new \stdClass();
      $retour->DeletedBsmDetIDs = "";
      $retour->BsmArticles=[];
      $articles_reserved=$retour;
    }

    // #####################################

    ///////// _____________ 

    $prev_bsms = prev_bsm::where('otp_id', '=', $OtpID)->get();
    $di_bsos = di_bso::where('ot_id', '=', $OtpID)->where('isCorrectif','=',0)->get();
    
    /*
    
    $di_bsos = di_bso::where('ot_id', '=', $id)->get();
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

    
    return response()->json(['ot' => $di_ot, 'intervenants' => $intervevantsIds, 'intervenants2' => $intervevants2, 'bsms' => $di_bsms, 'bsos' => $di_bsos]);


    */

    
    $prev_reservedintervenant=prev_reservedintervenant::where('otp_id','=',$OtpID)->first();
    if($prev_reservedintervenant){
    $IntReservationID=$prev_reservedintervenant->IntReservationID;
    $getIntReservation=$this->getIntReservation($IntReservationID);
    $getIntReservation=$getIntReservation->original;
    $reservedintervenants=$getIntReservation['reservedintervenants'];
    }else{
      $IntReservationID=null;
      $reservedintervenants=[];

    }


  $EquipementID = $intervention->equipement_id;
  $niveaux = app('App\Http\Controllers\Equipement\equipement')->getNiveausCompletDetByEquipementID($EquipementID);
  $niveaux = $niveaux->original;
  

    
    return response()->json(['otp'=>$otp,'intervention'=>$intervention,'bsms'=>$prev_bsms,'bsos' => $di_bsos,'reservation'=>$reservation,'articles_reserved'=>$articles_reserved,'reservedintervenants'=>$reservedintervenants,'IntReservationID'=>$IntReservationID,'niveaux'=>$niveaux]);

  }
  
  public function addOtp(Request $request){

    
    $reservedintervenants = $request->input('reservedintervenants');
    $InterventionID = $request->input('InterventionID');
    $form = $request->input('form');
    $articles_reserved = $request->input('articles_reserved');
    $UserID = JWTAuth::user()->id;

    $form['date_execution']=$this->DateFormYMD($form['date_execution']);
    $form['user_id']=$UserID;
    $prev_otp=prev_otp::create($form);
    $OtpID=$prev_otp->OtpID;

    

    if(count($articles_reserved)>0){
    $prev_reservation=prev_reservation::create([
      'otp_id' => $OtpID,
      'user_id' => $UserID,
    ]);
    $ReservationID=$prev_reservation->ReservationID;

    $BsmArticles = $request->articles_reserved;
    foreach ($BsmArticles as $article) {
      $Qte = $article['qte'];
      $ArticleID = $article['article_id'];
      $motif = $article['motif'];

      $di_bsm_det = prev_reservation_det::create([
        'reservation_id' => $ReservationID,
        'article_id' => $ArticleID,
        'qte' => $Qte,
        'motif' => $motif
      ]);

      $retour_reservation=app('App\Http\Controllers\Divers\generale')->ArticleReserved($ArticleID,$Qte);



      if (!$di_bsm_det) {
        $success = false;
      }
    }
  }



  // creaation de reservation s'il exist 
  if(count($reservedintervenants)>0){
  $req = new Request();
  $req->merge(['reservedintervenants' => $reservedintervenants]); 
  $this->addIntReservation($req,$OtpID);
  }

    
    // lancer la fonction generale si neccessaire béch tghayyir el planification si lazim yetbaddil 
    $this->MethodeGenerale( $OtpID,$this->DateFormYMD($form['date_execution']),'creation' );


    return response()->json($prev_otp);
  }

  public function editOtp(Request $request,$id){
    $form = $request->input('form');
    $UserID = JWTAuth::user()->id;

    $form['date_execution']=$this->DateFormYMD($form['date_execution']);

    $now = Carbon::now();
    $form['last_modif'] = $now;
    $form['modifieur_user_id'] = $UserID;

    $prev_otp=prev_otp::where('OtpID','=',$id)->update($form);
    prev_otp::where('OtpID','=',$id)->increment('NbDeModif', 1);

    // lancer la fonction generale si neccessaire béch tghayyir el planification si lazim yetbaddil 
    // bélik hiya a la execution  w baddil date execution  ... 

   
    return response()->json($prev_otp);
  }

  public function deleteOtp($id){
    $OtpID=$id;
    
    
    // suppression des bsms enAttente and bso ouvert
    $di_bsms=prev_bsm::where('otp_id','=',$OtpID)->where('statut','=','enAttente')->get();
    foreach($di_bsms as $di_bsm){
     $BsmID=$di_bsm['BsmID'];
     prev_bsm_det::where('bsm_id','=',$BsmID)->delete();
    }
    prev_bsm::where('otp_id','=',$OtpID)->where('statut','=','enAttente')->delete();
    $di_bsos=di_bso::where('ot_id','=',$OtpID)->where('isCorrectif','=',0)->where('statut','=','ouvert')->get();
    foreach($di_bsos as $di_bso){
     $BsoID=$di_bso['BsoID'];
     di_bso_det::where('bso_id','=',$BsoID)->delete();
    }
    di_bso::where('ot_id','=',$OtpID)->where('isCorrectif','=',0)->where('statut','=','ouvert')->delete();
    


    // lazim tarja3 tetteb3ath retour lel magsini féha somme mte3 les bso kolll fi retour wa7da
    $articles=Array();
    $articles_bsm=$this->getUnionBsmsOtp($OtpID);

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
      
      // nfassa5 el otp jémla
      $prev_reservedintervenant=prev_reservedintervenant::where('otp_id','=',$OtpID)->first();
      if($prev_reservedintervenant){
      $IntReservationID=$prev_reservedintervenant->IntReservationID;
      prev_reservedintervenant_det::where('intreservation_id','=',$IntReservationID)->delete();
      prev_reservedintervenant::where('otp_id','=',$OtpID)->delete();
      }
      
      $this->MethodeGenerale( $OtpID,null,'suppression'); // lazim 9bal ma exist ywalli 0

      prev_otp::where('OtpID','=',$OtpID)->update(['exist'=>false,'statut'=>'ferme']);
      
      // nghayyir ma yajibou taghyiroh fil intervention

     


  }

  

  public function getOtps(Request $request){
    
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

    $otps = prev_otp::where('prev_otps.exist','=',1)
      ->whereDate('prev_otps.date_execution', '>=', $datemin)
      ->whereDate('prev_otps.date_execution', '<=', $datemax)
      //->whereIn('prev_otps.type', $type)
      ->whereIn('prev_otps.statut', $statut)
      //->where('prev_interventions.isPlanified','=',1)
      
      /*
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
      */

      ->join('prev_interventions', function ($join) use($type) {
        $join->on('prev_interventions.InterventionID', '=', 'prev_otps.intervention_id')
        ->whereIn('prev_interventions.type', $type);
      })
      
      ->join('users', function ($join) {
        $join->on('users.id', '=', 'prev_interventions.user_id');
      })
      ->join('equi_equipements', function ($join) use ($nodes) {
        if (count($nodes) == 0) {
          $join->on('equi_equipements.EquipementID', '=', 'prev_interventions.equipement_id');
        } else {
          $join->on('equi_equipements.EquipementID', '=', 'prev_interventions.equipement_id')
            ->whereIn('equi_equipements.EquipementID', $nodes);
        }
      })
      ->leftjoin('equi_taches', function ($join) {
        $join->on('equi_taches.TacheID', '=', 'prev_interventions.tache_id');
      })
      ->Where(function ($query) use ($searchFilterText) {
        return $query
          ->where('equi_taches.tache', 'like', '%' . $searchFilterText . '%')
          ->orWhere('prev_interventions.type', 'like', '%' . $searchFilterText . '%')
          ->orWhere('equi_equipements.equipement', 'like', '%' . $searchFilterText . '%')
          ->orWhere('users.name', 'like', '%' . $searchFilterText . '%');
      })
      
      ->select('prev_otps.*','prev_interventions.type','prev_interventions.type','equi_taches.tache','users.name','equi_equipements.equipement')
      
      ->orderBy('prev_otps.date_execution', 'asc');

    
    $countQuery = $otps->count();
    $otps = $otps->skip($skipped)->take($itemsPerPage)->get();

    return response()->json(['otps' => $otps, 'me' => $me, 'total' => $countQuery]);

  }

  public function getBonpForAff($id){
    
    $bonp = prev_bonp::where('BonpID', '=', $id)
    ->join('users', function ($join) {
      $join->on('users.id', '=', 'prev_bonps.user_id');
    })
    ->leftjoin('users as modifieur', function ($join) {
      $join->on('modifieur.id', '=', 'prev_bonps.modifieur_user_id');
    })
    ->select(
      'prev_bonps.*',
      'users.name',
      'modifieur.name as modifieur_name'
    )
    ->first();


    $prev_otp = prev_otp::where('OtpID', '=', $bonp->otp_id)
    
    ->join('users', function ($join) {
      $join->on('users.id', '=', 'prev_otps.user_id');
    })
    /*
    ->leftjoin('users as modifieur', function ($join) {
      $join->on('modifieur.id', '=', 'prev_otps.modifieur_user_id');
    })
    */
    ->join('prev_interventions', function ($join) {
      $join->on('prev_interventions.InterventionID', '=', 'prev_otps.intervention_id');
    })
    ->leftjoin('equi_taches', function ($join) {
      $join->on('equi_taches.TacheID', '=', 'prev_interventions.tache_id');
    })
    ->select(
      'prev_otps.*',
      'users.name',
      //'modifieur.name as modifieur_name',
      'equi_taches.tache',
      'prev_interventions.equipement_id'
    )
    ->first();
  $EquipementID = $prev_otp->equipement_id;
  $niveaux = app('App\Http\Controllers\Equipement\equipement')->getNiveausCompletDetByEquipementID($EquipementID);
  $niveaux = $niveaux->original;
  
  $bonpintervenants = prev_bonp_intervenant::where('bonp_id', '=', $id)
        ->join('intervenants', function ($join) {
          $join->on('intervenants.IntervenantID', '=', 'prev_bonp_intervenants.intervenant_id');
        })
        ->leftjoin('equi_taches', function ($join) {
          $join->on('equi_taches.TacheID', '=', 'prev_bonp_intervenants.tache_id');
        })
        ->select(
          'prev_bonp_intervenants.BonpIntervenantID',
          'prev_bonp_intervenants.bonp_id',
          'prev_bonp_intervenants.intervenant_id',
          'intervenants.name',
          'prev_bonp_intervenants.date1',
          'prev_bonp_intervenants.datetime1 as time1',
          'prev_bonp_intervenants.date2',
          'prev_bonp_intervenants.datetime2 as time2',
          'prev_bonp_intervenants.tache_id',
          'equi_taches.tache',
          'prev_bonp_intervenants.description'
        )
        ->get();

    //$articles=Array();
    $articles=$this->getUnionBsmsOtp($bonp->otp_id);
      foreach($articles as $article){
          $article->qteu= $article->qtea - $this->getTTqtear_efTTqter_f($id,$article->article_id)->TTqtear_ef;
          $article->qter= $this->getTTqtear_efTTqter_f($id,$article->article_id)->TTqter_f;
      }

    //$pdrs=$this->getUnionBsoOtp($bonp->otp_id);

    $bsos=$this->GetUnionAllBsosByOtpID($bonp->otp_id);
    

  return response()->json(['bonp' => $bonp,'niveaux'=>$niveaux,'otp'=>$prev_otp,'intervenants'=>$bonpintervenants,'articles'=>$articles,'bsos'=>$bsos]);
  
  }

  

  public function getBonp(Request $request)
  {

    //return response()->json($request);
    $OtpID = $request->input('OtpID');
    $BonpID = $request->input('BonpID');
    $isBonpExist = false;
    if ($OtpID == 0) {
      $bonp_id = prev_bonp::where('BonpID', '=', $BonpID)->first();
      $OtpID = $bonp_id->otp_id;
    } else {
      $NbDeBonpsExist = prev_bonp::where('otp_id', '=', $OtpID)->where('exist', '=', 1)->get();
      if (count($NbDeBonpsExist) > 0) {
        $isBonpExist = true;
      }
    }
    
    $articles=[]; // 9ahwa  
  
    
    $articles=$this->getUnionBsmsOtp($OtpID);
    if ($BonpID>0) {
      foreach($articles as $article){
          $article->qteu= $article->qtea - $this->getTTqtear_efTTqter_f($BonpID,$article->article_id)->TTqtear_ef;
          $article->qter= $this->getTTqtear_efTTqter_f($BonpID,$article->article_id)->TTqter_f;
      }
    }

    
    

    

    $UserID = JWTAuth::user()->id;
    $me = app('App\Http\Controllers\Divers\generale')->getUserPosts($UserID);
    $me = $me->original;
    $me = $me[0];
    $posts = $me->posts;
    $isAdmin = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, ['Admin']);
    $isMethode = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, ['Methode']);

    $prev_bonp = "";
    
    $prev_otp = prev_otp::where('OtpID', '=', $OtpID)
      ->join('users', function ($join) {
        $join->on('users.id', '=', 'prev_otps.user_id');
      })
      ->leftjoin('users as modifieur', function ($join) {
        $join->on('modifieur.id', '=', 'prev_otps.modifieur_user_id');
      })
      ->join('prev_interventions', function ($join) {
        $join->on('prev_interventions.InterventionID', '=', 'prev_otps.intervention_id');
      })
      ->leftjoin('equi_taches', function ($join) {
        $join->on('equi_taches.TacheID', '=', 'prev_interventions.tache_id');
      })

      ->leftjoin('equi_equipements', function ($join) { // nooooooooooooooooooooooooooode
        $join->on('equi_equipements.EquipementID', '=', 'prev_interventions.equipement_id');
      })


      
     
      /*
      ->Where(function ($query) use ($isAdmin, $isMethode, $UserID) {
        if (($isAdmin || $isMethode)) {
          return $query;
        } else {
          return $query->where('di_dis.recepteur_user_id', '=', $UserID);
          //->orWhere('di_dis.demandeur_user_id','=',$UserID);
        }
      })
      */
      ->select(
        'prev_otps.*',
        'users.name',
        'modifieur.name as modifieur_name',
        'equi_taches.tache',
        'prev_interventions.equipement_id',
        'equi_equipements.equipement', // noooooooooooooooooooooooooode
        'equi_equipements.Niv' // noooooooooooooooooooooooooooooooode 
      )
      ->first();

    $EquipementID = $prev_otp->equipement_id;
    $niveaux = app('App\Http\Controllers\Equipement\equipement')->getNiveausCompletDetByEquipementID($EquipementID);
    $niveaux = $niveaux->original;

    

    
    if ($BonpID == 0) {

      $intervevants=[]; // 9ahwa
      $bonpintervenants=[]; // 9ahwa
/*
      $intervevants = prev_otp_intervenant::where('otp_id', '=', $OtpID)
        ->join('intervenants', function ($join) {
          $join->on('intervenants.IntervenantID', '=', 'prev_otp_intervenants.intervenant_id');
        })->select('intervenants.*')->get();
      
      $bonintervenants = array();
      $now = Carbon::now();
      foreach ($intervevants as $intervenant) {
        $bon_intervenant = new \stdClass();
        $bon_intervenant->BonIntervenantID = null;
        $bon_intervenant->bon_id = null;
        $bon_intervenant->intervenant_id = $intervenant['IntervenantID'];
        $bon_intervenant->name = $intervenant['name'];
        $bon_intervenant->date1 = $di_ot['datetime'];
        $bon_intervenant->time1 = $di_ot['datetime'];
        $bon_intervenant->date2 = $di_ot['datetime'];
        $bon_intervenant->time2 = null;
        $bon_intervenant->tache_id = null;
        $bon_intervenant->tache = null;
        $bon_intervenant->description = '';
        array_push($bonintervenants, $bon_intervenant);
      }
*/


$prev_reservedintervenant=prev_reservedintervenant::where('otp_id','=',$OtpID)->first();
if($prev_reservedintervenant){
$IntReservationID=$prev_reservedintervenant->IntReservationID;

$bonpintervenants=prev_reservedintervenant_det::where('intreservation_id','=',$IntReservationID)
->join('intervenants', function ($join) {
  $join->on('intervenants.IntervenantID', '=', 'prev_reservedintervenant_dets.intervenant_id');
})
->leftjoin('equi_taches', function ($join) {
  $join->on('equi_taches.TacheID', '=', 'prev_reservedintervenant_dets.tache_id');
})
->select(
  
  //'NULL as BonpIntervenantID',
  //'NULL as bonp_id',
  
  'prev_reservedintervenant_dets.ReservationIntervenantID as BonpIntervenantID',
  'prev_reservedintervenant_dets.intreservation_id as bonp_id',
  'prev_reservedintervenant_dets.intervenant_id',
  'intervenants.name',
  'prev_reservedintervenant_dets.date1',
  'prev_reservedintervenant_dets.datetime1 as time1',
  'prev_reservedintervenant_dets.date2',
  'prev_reservedintervenant_dets.datetime2 as time2',
  'prev_reservedintervenant_dets.tache_id',
  'equi_taches.tache',
  'prev_reservedintervenant_dets.description'
)
->get();



}else{
  $bonpintervenants=[];
}

      $prev_bonp = prev_bonp::where('BonpID', '=', $BonpID)
        ->join('users', function ($join) {
          $join->on('users.id', '=', 'prev_bonps.user_id');
        })
        ->leftjoin('users as modifieur', function ($join) {
          $join->on('modifieur.id', '=', 'prev_bonps.modifieur_user_id');
        })
        ->select('prev_bonps.*', 'users.name', 'modifieur.name as modifieur')
        ->first();



    } else {

      //return response()->json('zab');

      $bonpintervenants = prev_bonp_intervenant::where('bonp_id', '=', $BonpID)
        ->join('intervenants', function ($join) {
          $join->on('intervenants.IntervenantID', '=', 'prev_bonp_intervenants.intervenant_id');
        })
        ->leftjoin('equi_taches', function ($join) {
          $join->on('equi_taches.TacheID', '=', 'prev_bonp_intervenants.tache_id');
        })
        ->select(
          'prev_bonp_intervenants.BonpIntervenantID',
          'prev_bonp_intervenants.bonp_id',
          'prev_bonp_intervenants.intervenant_id',
          'intervenants.name',
          'prev_bonp_intervenants.date1',
          'prev_bonp_intervenants.datetime1 as time1',
          'prev_bonp_intervenants.date2',
          'prev_bonp_intervenants.datetime2 as time2',
          'prev_bonp_intervenants.tache_id',
          'equi_taches.tache',
          'prev_bonp_intervenants.description'
        )
        ->get();

      $prev_bonp = prev_bonp::where('BonpID', '=', $BonpID)
        ->join('users', function ($join) {
          $join->on('users.id', '=', 'prev_bonps.user_id');
        })
        ->leftjoin('users as modifieur', function ($join) {
          $join->on('modifieur.id', '=', 'prev_bonps.modifieur_user_id');
        })
        ->select('prev_bonps.*', 'users.name', 'modifieur.name as modifieur')
        ->first();
    }

    return response()->json(['otp' => $prev_otp, 'bonpintervenants' => $bonpintervenants, 'niveaux' => $niveaux, 'bonp' => $prev_bonp, 'isBonpExist' => $isBonpExist,'articles'=>$articles]); //,'bsms'=>$di_bsms,'bsos'=>$di_bsos]);

  }



  public function addBonp(Request $request, $OtpID)
  {
    
    $success=true;
    
    $BonIntervenants = $request->BonIntervenants;
    $articles_r = $request->articles;


    $rapport = $request->Rapport;


    //$date_cloture = $this->DateFormYMD($request->date_cloture);
    $date_cloture = $this->choose_date_cloture($BonIntervenants);

    
    //return response()->json($date_cloture);

    $DeletedBonpIntervenantIDs = $request->DeletedBonpIntervenantIDs;
    $UserID = JWTAuth::user()->id;

    
    // Partie 1 date time
    $prev_bonp = prev_bonp::create([
      'otp_id' => $OtpID,
      'user_id' => $UserID,
      'date_cloture'=>$date_cloture,
      'rapport' => $rapport
    ]);
    $BonpID = $prev_bonp->BonpID;

    
    // changement de statut otp ferme
    prev_otp::where('OtpID','=',$OtpID)->update(['statut'=>'ferme']);
    
    
    

    
    // suppression des bsms enAttente and bso ouvert
    $di_bsms=prev_bsm::where('otp_id','=',$OtpID)->where('statut','=','enAttente')->get();
    foreach($di_bsms as $di_bsm){
     $BsmID=$di_bsm['BsmID'];
     prev_bsm_det::where('bsm_id','=',$BsmID)->delete();
    }
    prev_bsm::where('otp_id','=',$OtpID)->where('statut','=','enAttente')->delete();

    $di_bsos=di_bso::where('ot_id','=',$OtpID)->where('isCorrectif','=',0)->where('statut','=','ouvert')->get();
    foreach($di_bsos as $di_bso){
     $BsoID=$di_bso['BsoID'];
     di_bso_det::where('bso_id','=',$BsoID)->delete();
    }
    di_bso::where('ot_id','=',$OtpID)->where('isCorrectif','=',0)->where('statut','=','ouvert')->delete();
    
    

    // chouf bélik famma des autres changements 



    foreach ($BonIntervenants as $BonIntervenant) {

      $date1 = $this->DateFormYMD($BonIntervenant['date1']);
      $time1 = $this->TimeFormHM($BonIntervenant['time1']);
      $date2 = $this->DateFormYMD($BonIntervenant['date2']);
      $time2 = $this->TimeFormHM($BonIntervenant['time2']);
      $datetime1 = app('App\Http\Controllers\Correctif\correctif')->combineDateTime($BonIntervenant['date1'], $BonIntervenant['time1']);
      $datetime2 = app('App\Http\Controllers\Correctif\correctif')->combineDateTime($BonIntervenant['date2'], $BonIntervenant['time2']);

      prev_bonp_intervenant::create([
        'bonp_id' => $BonpID,
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
    $articles_bsm=$this->getUnionBsmsOtp($OtpID);


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
         $value->qtear = $article->qtea - $article->qteu;
         array_push($array_pour_LancerNouveauRetour,$value);
      }
     }
     $RetourID=$this->LancerNouveauRetour($BonpID,$array_pour_LancerNouveauRetour,$OtpID);
     if(!$RetourID){$success=false;}
   }

   
   $this->MethodeGenerale( $OtpID,$date_cloture,'cloture');
   $this->MiseAjourIntervention('add',$OtpID,$BonIntervenants);


   // supression des articles reserve
  $prev_reservation=prev_reservation::where('otp_id','=',$OtpID)->first();
  if($prev_reservation){
  $ReservationID=$prev_reservation->ReservationID;
  $this->ViderArticlesReserved($ReservationID);
  }

  // supression des intervenants reservées
  $prev_reservedintervenant=prev_reservedintervenant::where('otp_id','=',$OtpID)->first();
      if($prev_reservedintervenant){
      $IntReservationID=$prev_reservedintervenant->IntReservationID;
      prev_reservedintervenant_det::where('intreservation_id','=',$IntReservationID)->delete();
      prev_reservedintervenant::where('otp_id','=',$OtpID)->delete();
      }
      

   /*
   $duree_intervenants = $this->duree_intervenants($BonIntervenants);
   $h=$duree_intervenants->h;
   $m=$duree_intervenants->m;
   $nb_operateur=count($BonIntervenants);
   */

    
   //return response()->json($array_pour_LancerNouveauRetour); // kénét 9bal design 
   return response()->json(['success'=>$success,'bonp'=>$prev_bonp]); 
   
  
   



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

  
  return response()->json($success);

  }



  public function choose_date_cloture($BonIntervenants){

    $date_cloture=null;
    foreach ($BonIntervenants as $BonIntervenant) {
      $date2 = $this->DateFormYMD($BonIntervenant['date2']);
      if($date2>$date_cloture){
        $date_cloture=$date2;
      }
    }
    return $date_cloture;
  }

  

  

  public function editBonp(Request $request, $BonpID)
  {
    
    $success=true;
    // ma fhémtich 3léch nfasa5 fihom marra o5ra mani fassa5thom fil addBonp w kif kif fil correctif 
    $ot_id_prev_bonp=prev_bonp::where('BonpID','=',$BonpID)->first();
    $ot_id=$ot_id_prev_bonp->otp_id;

    $di_bsos=di_bso::where('ot_id','=',$ot_id)->where('isCorrectif','=',0)->where('statut','=','ouvert')->get();
    foreach($di_bsos as $di_bso){
     $BsoID=$di_bso['BsoID'];
     di_bso_det::where('bso_id','=',$BsoID)->delete();
     
    }
    di_bso::where('ot_id','=',$ot_id)->where('isCorrectif','=',0)->where('statut','=','ouvert')->delete();
    
    

    $BonIntervenants = $request->BonIntervenants;
    $articles_r = $request->articles;
    
    $rapport = $request->Rapport;
    
    //$date_cloture = $this->DateFormYMD($request->date_cloture);

    $date_cloture = $this->choose_date_cloture($BonIntervenants);


    $DeletedBonpIntervenantIDs = $request->DeletedBonpIntervenantIDs;
    $UserID = JWTAuth::user()->id;
    
    $now = Carbon::now();
    $prev_bonp_sec=prev_bonp::where('BonpID', '=', $BonpID)->where('exist', '=', 1)->update([
      'date_cloture'=>$date_cloture,
      'rapport' => $rapport,
      'last_modif' => $now,
      'modifieur_user_id' => $UserID
    ]);
    prev_bonp::where('BonpID', '=', $BonpID)->where('exist', '=', 1)->increment('NbDeModif', 1);

  
    //return response()->json($BonIntervenants);

    foreach ($BonIntervenants as $BonIntervenant) {

      $date1 = $this->DateFormYMD($BonIntervenant['date1']);
      $time1 = $this->TimeFormHM($BonIntervenant['time1']);
      $date2 = $this->DateFormYMD($BonIntervenant['date2']);
      $time2 = $this->TimeFormHM($BonIntervenant['time2']);
      $datetime1 = app('App\Http\Controllers\Correctif\correctif')->combineDateTime($BonIntervenant['date1'], $BonIntervenant['time1']);
      $datetime2 = app('App\Http\Controllers\Correctif\correctif')->combineDateTime($BonIntervenant['date2'], $BonIntervenant['time2']);

      if ($BonIntervenant['BonpIntervenantID'] == null) {
        $prev_bonp_intervenant =prev_bonp_intervenant::create([
          'bonp_id' => $BonpID,
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
        if (!$prev_bonp_intervenant) {
          $success = false;
        }
      }

      if ($BonIntervenant['BonpIntervenantID'] > 0) {
        $prev_bonp_intervenant = prev_bonp_intervenant::where('BonpIntervenantID', '=', $BonIntervenant['BonpIntervenantID'])->update([
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

    $arrIDsToDelete = explode(',', $DeletedBonpIntervenantIDs, -1);
    foreach ($arrIDsToDelete as $ID) {
      $prev_bonp_intervenant = prev_bonp_intervenant::where('BonpIntervenantID', '=', $ID)->delete();
      if (!$prev_bonp_intervenant) {
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
    $ArrayArticlesTTRetour=$this->ReturnArrayArticlesTTRetourFerme($BonpID);
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

              if($qtear_nr<$qter_ar){
                 // pas logique 
                }
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
    else if($this->IsAnyArticlePuExistInRetourEA($BonpID,$articles_nv_pu)){$IsNouveauRetour=true;}
  
       
       
       if(count($articles_nv_pu)>0){
        // nchouf esque famma changement necessaire walla ? famma supprussion ou envoie de notification walla ...
        
        foreach($articles_nv_pu as $article_nv_pu){
          $article_id=$article_nv_pu->article_id;
          $qtepu=$article_nv_pu->qtepu;
         
          $ttlignesperdudunarticle=prev_bon_retour::where('bonp_id','=',$BonpID)->where('statut','=','ferme')
          ->join('prev_bon_retour_dets', function ($join) use($article_id) {
            $join->on('prev_bon_retour_dets.retour_id', '=', 'prev_bon_retours.RetourID')
            ->where('prev_bon_retour_dets.article_id','=',$article_id)
            ->whereRaw('prev_bon_retour_dets.qtear > prev_bon_retour_dets.qter');
          })
          ->select('prev_bon_retour_dets.*')
          ->orderByRaw('(prev_bon_retour_dets.qtear - prev_bon_retour_dets.qter) desc')
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
         $DeleteAllRetourNonFerme=$this->DeleteAllRetourNonFerme($BonpID);
         $updating=true;
    }

    if(count($articles_nv_retour)>0){

      // lancer un nv retour
      $RetourID=$this->LancerNouveauRetour($BonpID,$articles_nv_retour,$ot_id);
      $updating=true;


    }
    
    
    $this->MethodeGenerale($ot_id,$date_cloture,'modification');


  
    $this->MiseAjourIntervention('edit',$BonpID,$BonIntervenants);


   return response()->json(['success'=>$success,'success_edit'=>$prev_bonp_sec]);

    //##################################################################

  }




  
  public function getBonps(Request $request){

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

    //$type = $filter['type'];
    //$parametrage = $filter['parametrage'];

    $UserID = JWTAuth::user()->id;
    $me = app('App\Http\Controllers\Divers\generale')->getUserPosts($UserID);
    $me = $me->original;
    $me = $me[0];
    $posts = $me->posts;

    $isAdmin = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, ['Admin']);
    $isMethode = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, ['Methode']);
    $OthersAuthorized = ['ChefDePoste', 'ResponsableMaintenance'];
    $isOthersAuthorized = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, $OthersAuthorized);

    $bonps = prev_bonp::
        whereDate('prev_bonps.created_at', '>=', $datemin)
        ->whereDate('prev_bonps.created_at', '<=', $datemax)
       // whereIn('prev_interventions.type', $type)
      //->whereIn('prev_interventions.parametrage', $parametrage)
      //->where('prev_interventions.isPlanified','=',1)
      
      /*
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
      */

      ->join('users', function ($join) {
        $join->on('users.id', '=', 'prev_bonps.user_id');
      })
      /*
      ->leftjoin('users as modifieur', function ($join) {
        $join->on('modifieur.id', '=', 'prev_otps.modifieur_user_id');
      })
      */
      

      ->join('prev_otps', function ($join) {
        $join->on('prev_otps.OtpID', '=', 'prev_bonps.otp_id');
      })
      ->join('prev_interventions', function ($join) {
        $join->on('prev_interventions.InterventionID', '=', 'prev_otps.intervention_id');
      })
      ->join('equi_equipements', function ($join) use ($nodes) {
        if (count($nodes) == 0) {
          $join->on('equi_equipements.EquipementID', '=', 'prev_interventions.equipement_id');
        } else {
          $join->on('equi_equipements.EquipementID', '=', 'prev_interventions.equipement_id')
            ->whereIn('equi_equipements.EquipementID', $nodes);
        }
      })

      /*
      ->leftjoin('equi_taches', function ($join) {
        $join->on('equi_taches.TacheID', '=', 'prev_interventions.tache_id');
      })
      */
      ->Where(function ($query) use ($searchFilterText) {
        return $query
          //->where('equi_taches.tache', 'like', '%' . $searchFilterText . '%')
          //->orWhere('prev_interventions.type', 'like', '%' . $searchFilterText . '%')
          //->orWhere('prev_interventions.parametrage', 'like', '%' . $searchFilterText . '%')
          //->orWhere('equi_equipements.equipement', 'like', '%' . $searchFilterText . '%');
          ->Where('users.name', 'like', '%' . $searchFilterText . '%');
      })
      ->select('prev_bonps.*','users.name','equi_equipements.equipement')
      ->orderBy('prev_bonps.created_at', 'asc');


    
    $countQuery = $bonps->count();
    $bonps = $bonps->skip($skipped)->take($itemsPerPage)->get();

    return response()->json(['bonps' => $bonps, 'me' => $me, 'total' => $countQuery]);


  }



  public function MiseAjourIntervention($type,$ID,$BonIntervenants){

     
   $duree = $this->duree_intervenants($BonIntervenants);
   $nb_operateur=count($BonIntervenants);
   
   if($type=='edit'){
   // $ID = BonpID 
   $otp_id_for_update=prev_bonp::where('BonpID','=',$ID)->first();
   $otp_id_update=$otp_id_for_update->otp_id;
   $prev_otp_for_update=prev_otp::where('OtpID','=',$otp_id_update)->first();
   $InterventionIDForUpdate=$prev_otp_for_update->intervention_id;
   }else{
   // $ID = OtpID
   $prev_otp_for_update=prev_otp::where('OtpID','=',$ID)->first();
   $InterventionIDForUpdate=$prev_otp_for_update->intervention_id;
   }

   $this->UpdateIntervention($InterventionIDForUpdate,$nb_operateur,$duree->h,$duree->m);

  }


  public function duree_intervenants($BonIntervenants){
    $duree_h_totale=0;
    $duree_m_totale=0;
    foreach ($BonIntervenants as $BonIntervenant) {
      $date1 = $this->DateFormYMD($BonIntervenant['date1']);
      $time1 = $this->TimeFormHM ($BonIntervenant['time1']);
      $date2 = $this->DateFormYMD($BonIntervenant['date2']);
      $time2= $this->TimeFormHM ($BonIntervenant['time2']);

      $duree_h=2;
      $duree_m=10;

      $duree_h_totale+=$duree_h;
      $duree_m_totale+=$duree_m;
    }
    
    $duree = new \stdClass();
    $duree->h =$duree_h_totale;
    $duree->m =$duree_m_totale;

    return $duree;
  }

  public function UpdateIntervention($InterventionID,$nb_operateur,$h,$m){
   
    $totale_en_m=$h*60+$m;

    $prev_intervention=prev_intervention::where('InterventionID','=',$InterventionID)->first();
    $intervention_nb_operateur=$prev_intervention->nb_operateur;
    $intervention_h=$prev_intervention->h;
    $intervention_m=$prev_intervention->m;
    $intervention_totale_en_m=$intervention_h*60 + $intervention_m;


    if($nb_operateur<$intervention_nb_operateur){
      prev_intervention::where('InterventionID','=',$InterventionID)->update(['nb_operateur'=>$nb_operateur]);
    }

    if($totale_en_m<$intervention_totale_en_m){
      prev_intervention::where('InterventionID','=',$InterventionID)->update(['h'=>$h,'m'=>$m]);
    }
    

  }





  public function getUnionBsmsOtp($OtID)
  {
    $articles = array();
    $bsms = prev_bsm::where('otp_id', '=', $OtID)->where('statut', '=', 'accepted')->where('exist', '=', 1)->get();

    foreach ($bsms as $bsm) {

      $BsmID = $bsm['BsmID'];

      $di_response_bsm = prev_response_bsm::where('bsm_id', '=', $BsmID)->where('exist', '=', 1)->first();
      $ResponseBsmID = $di_response_bsm->ResponseBsmID;
      $di_response_bsm_dets = prev_response_bsm_det::where('response_bsm_id', '=', $ResponseBsmID)
        ->join('prev_bsm_dets', function ($join) {
          $join->on('prev_bsm_dets.BsmDetID', '=', 'prev_response_bsm_dets.bsm_det_id');
        })
        ->join('articles', function ($join) {
          $join->on('articles.ArticleID', '=', 'prev_bsm_dets.article_id');
        })
        ->select('articles.des', 'prev_bsm_dets.article_id','prev_bsm_dets.qte as qted','prev_response_bsm_dets.qte as qtea')
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


  public function GetUnionAllBsosByOtpID($OtpID){

    $array_bsos=array();
    $bsos = di_bso::where('ot_id', '=', $OtpID)->where('isCorrectif','=',0)->where('exist','=',1)->get();

    foreach ($bsos as $bso){
    $BsoID=$bso['BsoID'];
    $req = new Request();
    $req->merge(['Src' =>'preventif']); 
    $BsoWithResponse = app('App\Http\Controllers\Correctif\correctif')->getBsoWithResponse($req,$BsoID);
    $BsoWithResponse = $BsoWithResponse->original;
    array_push($array_bsos,$BsoWithResponse);
    }
    
    //return response()->json(['bsos'=>$array_bsos]);
    return $array_bsos;

  }



  public function correctIArray($array){
    $ret_array=Array();
    foreach($array as $key => $value){
      array_push($ret_array,$value);
    }
    return $ret_array;
  }





  function LancerNouveauRetour($BonID,$articles,$OtID){
    // chaque ligne de array articles doit contenir 
    // un objet contient les deux colones : article_id et qtear
    $success=true;
    $UserID = JWTAuth::user()->id;
    $di_bon_retour=prev_bon_retour::create([
      'bonp_id' => $BonID,
      'otp_id'=>$OtID,
      'user_id'=>$UserID
    ]);
    if(!$di_bon_retour){$success=false;}
    $RetourID=$di_bon_retour->RetourID;
   
   foreach($articles as $article){
      if($article->qtear>0){
      $di_bon_retour_det=prev_bon_retour_det::create([
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


 
  
  public function getTTqtear_efTTqter_f($BonID,$article_id){
    // qteu = TTqtea - 
    // TTqtear_ef
    // qter = TTqter_f
    

    $TTqter_f=0;
    $di_bon_retours=prev_bon_retour::where('bonp_id','=',$BonID)->where('statut','=','ferme')->get();
    foreach ($di_bon_retours as $di_bon_retour) {
      $RetourID=$di_bon_retour['RetourID'];
      $di_bon_retour_dets=prev_bon_retour_det::where('retour_id','=',$RetourID)->where('article_id','=',$article_id)->get();
      foreach ($di_bon_retour_dets as $di_bon_retour_det) {
        $TTqter_f+=$di_bon_retour_det['qter'];
      }
    }

    $TTqtear_ef=0;
    $di_bon_retours=prev_bon_retour::where('bonp_id','=',$BonID)->get();
    foreach ($di_bon_retours as $di_bon_retour) {
      $RetourID=$di_bon_retour['RetourID'];
      $di_bon_retour_dets=prev_bon_retour_det::where('retour_id','=',$RetourID)->where('article_id','=',$article_id)->get();                        
      foreach ($di_bon_retour_dets as $di_bon_retour_det) {
        $TTqtear_ef+=$di_bon_retour_det['qtear'];
      }
    }

    $TTqtear_e=0;
    $di_bon_retours=prev_bon_retour::where('bonp_id','=',$BonID)->where('statut','=','enAttente')->get();
    foreach ($di_bon_retours as $di_bon_retour) {
      $RetourID=$di_bon_retour['RetourID'];
      $di_bon_retour_dets=prev_bon_retour_det::where('retour_id','=',$RetourID)->where('article_id','=',$article_id)->get();                        
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


  public function ReturnArrayArticlesTTRetourFerme($BonID){
    $articles=Array();
    $di_bon_retours=prev_bon_retour::where('bonp_id','=',$BonID)->where('statut','=','ferme')->get();
    foreach ($di_bon_retours as $di_bon_retour) {
      $RetourID=$di_bon_retour['RetourID'];
      $di_bon_retour_dets=prev_bon_retour_det::where('retour_id','=',$RetourID)
      ->join('articles', function ($join) {
        $join->on('articles.ArticleID', '=', 'prev_bon_retour_dets.article_id');
      })->select('articles.des', 'prev_bon_retour_dets.*')->get();
     
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

  
  public function IsArticleIDExistInArrayOfObject($ArticleID,$array){
    $exist=false;
    foreach($array as $obj){
      $article_id=$obj->article_id;
      if($article_id == $ArticleID){ $exist=true;}
    }
    return $exist;
  }


  
  public function IsAnyArticlePuExistInRetourEA($BonID,$array){
    $di_bon_retours=prev_bon_retour::where('bonp_id','=',$BonID)->where('statut','=','enAttente')->get();
    foreach($di_bon_retours as $di_bon_retour){
      $RetourID=$di_bon_retour['RetourID'];
      $di_bon_retour_dets=prev_bon_retour_det::where('retour_id','=',$RetourID)->get();
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

  public function DeleteAllRetourNonFerme($BonID){
    $success=true;
    $di_bon_retours=prev_bon_retour::where('bonp_id','=',$BonID)->where('statut','=','enAttente')->get();
    foreach($di_bon_retours as $di_bon_retour){
      $RetourID=$di_bon_retour['RetourID'];
      $DeleteRetourByRetourID = $this->DeleteRetourByRetourID($RetourID);
      if(!$DeleteRetourByRetourID){ $success=false;}
    }
    return $success;
  }
  
  public function DeleteRetourByRetourID($RetourID){
    $success=true;
    $di_bon_retour_dets=prev_bon_retour_det::where('retour_id','=',$RetourID)->get();
    prev_bon_retour_det::where('retour_id','=',$RetourID)->delete();
    $di_bon_retour=prev_bon_retour::where('RetourID','=',$RetourID)->first();
    $BonID=$di_bon_retour->bonp_id;
    $di_bon_retour_delete=prev_bon_retour::where('RetourID','=',$RetourID)->delete();
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


   
  public function PerduUtilise($array){
    $UserID = JWTAuth::user()->id;
    foreach($array as $ligne){
      $RetourDetID=$ligne->RetourDetID;
      $retour_id=$ligne->retour_id;
      $article_id=$ligne->article_id;
      $value=$ligne->value;
      prev_bon_retour_det::where('RetourDetID','=',$RetourDetID)->increment('qtear',(-1*$value));
      //$di_bon_retour_det=di_bon_retour_det::where('RetourDetID','=',$RetourDetID)->
      // notification
      // tableau perdu generale increment 
      
      $retour_perdu=app('App\Http\Controllers\Divers\generale')->UpdatePerduByArticleID($article_id,(-1*$value));

      prev_bon_retour_hist::create([
        'retour_id'=>$retour_id,
        'article_id'=>$article_id,
        'user_id'=>$UserID,
        'isModification'=>false,
        'type'=>'pu',
        'value'=>$value
      ]);
    }
  }



  public function addReservationBsm(Request $request, $id)
  {

   
    $success = true;

    $UserID = JWTAuth::user()->id;


    $prev_reservation = prev_reservation::create([
      'otp_id' => $id,
      'user_id' => $UserID,
    ]);

    $ReservationID = $prev_reservation->ReservationID;
    $reservation = prev_reservation::where('ReservationID', '=', $ReservationID)->first();

    if (!$prev_reservation) {
      $success = false;
    }

    $BsmArticles = $request->BsmArticles;
    foreach ($BsmArticles as $article) {
      $Qte = $article['qte'];
      $ArticleID = $article['article_id'];
      $motif = $article['motif'];

      $prev_reservation_det = prev_reservation_det::create([
        'reservation_id' => $ReservationID,
        'article_id' => $ArticleID,
        'qte' => $Qte,
        'motif' => $motif
      ]);

      $retour_reservation=app('App\Http\Controllers\Divers\generale')->ArticleReserved($ArticleID,$Qte);
      

      if (!$prev_reservation_det) {
        $success = false;
      }
    }




    $di_bsm_dets = prev_reservation_det::where('reservation_id', '=', $ReservationID)
      ->join('articles', function ($join) {
        $join->on('articles.ArticleID', '=', 'prev_reservation_dets.article_id');
      })
      ->join('unites', function ($join) {
        $join->on('unites.UniteID', '=', 'articles.unite_id');
      })
      ->select(
      'prev_reservation_dets.ReservationDetID as BsmDetID',
      'prev_reservation_dets.reservation_id as bsm_id',
      'prev_reservation_dets.article_id',
      'prev_reservation_dets.qte',
      'prev_reservation_dets.motif',
      'prev_reservation_dets.exist',
      'prev_reservation_dets.created_at',
      'prev_reservation_dets.updated_at',

      
      'articles.des', 'articles.stock', 'unites.unite')
      ->get();

    // DeletedBsmArticleIDs
    // BsmArticles 





    return response()->json(['success' => $success, 'reservation' => $reservation,'articles_reserved'=>$di_bsm_dets]);
   
  }

  

  public function getReservationBsmByID(Request $request,$id)
  {

    prev_reservation::where('ReservationID', '=', $id)->first();
    $di_bsm_dets = prev_reservation_det::where('reservation_id', '=', $id)
      ->join('articles', function ($join) {
        $join->on('articles.ArticleID', '=', 'prev_reservation_dets.article_id');
      })
      ->join('unites', function ($join) {
        $join->on('unites.UniteID', '=', 'articles.unite_id');
      })
      ->select(
      'prev_reservation_dets.ReservationDetID as BsmDetID',
      'prev_reservation_dets.reservation_id as bsm_id',
      'prev_reservation_dets.article_id',
      'prev_reservation_dets.qte',
      'prev_reservation_dets.motif',
      'prev_reservation_dets.exist',
      'prev_reservation_dets.created_at',
      'prev_reservation_dets.updated_at',

      
      'articles.des', 'articles.stock', 'unites.unite')
      ->get();

    // DeletedBsmArticleIDs
    // BsmArticles 

    $Bsm = new \stdClass();
    $Bsm->DeletedBsmDetIDs = "";
    $Bsm->BsmArticles = $di_bsm_dets;

    return response()->json($Bsm);

  }




  
  public function editReservationBsm(Request $request, $id)
  {

    $success = true;

    $BsmArticles = $request->BsmArticles;
    $DeletedBsmDetIDs = $request->DeletedBsmDetIDs;


    foreach ($BsmArticles as $article) {

      if ($article['BsmDetID'] == null) {

        $Qte = $article['qte'];
        $ArticleID = $article['article_id'];
        $motif = $article['motif'];

        $di_bsm_det = prev_reservation_det::create([
          'reservation_id' => $id,
          'article_id' => $ArticleID,
          'qte' => $Qte,
          'motif' => $motif
        ]);

        $retour_reservation=app('App\Http\Controllers\Divers\generale')->ArticleReserved($ArticleID,$Qte);


        if (!$di_bsm_det) {
          $success = false;
        }
      }

      if ($article['BsmDetID'] > 0) {

        $di_bsm_det_pour_reservation = prev_reservation_det::where('ReservationDetID', '=', $article['BsmDetID'])->first();
        //$ArticleID_res=$di_bsm_det_pour_reservation->article_id;
        $Qte_res=$di_bsm_det_pour_reservation->qte;

        $Qte = $article['qte'];
        $ArticleID = $article['article_id'];
        $motif = $article['motif'];

        $di_bsm_det = prev_reservation_det::where('ReservationDetID', '=', $article['BsmDetID'])->update([
          'article_id' => $ArticleID,
          'qte' => $Qte,
          'motif' => $motif
        ]);

        
        $retour_reservation=app('App\Http\Controllers\Divers\generale')->ArticleReserved($ArticleID,($Qte-$Qte_res));



      }
    }
    
    $arrIDsToDelete = explode(',', $DeletedBsmDetIDs, -1);
    foreach ($arrIDsToDelete as $ID) {

      $di_bsm_det_pour_comparaison = prev_reservation_det::where('ReservationDetID', '=', $ID)->first();
      $di_bsm_det = prev_reservation_det::where('ReservationDetID', '=', $ID)->delete();
      $ArticleID=$di_bsm_det_pour_comparaison->article_id;
      $Qte=$di_bsm_det_pour_comparaison->qte;
      $retour_reservation=app('App\Http\Controllers\Divers\generale')->ArticleReserved($ArticleID,-$Qte);
      
      if (!$di_bsm_det) {
        $success = false;
      }
    }
    

    $prev_reservation_det_resultat=
    prev_reservation_det::where('reservation_id','=',$id)
    ->join('articles', function ($join) {
      $join->on('articles.ArticleID', '=', 'prev_reservation_dets.article_id');
    })
    ->join('unites', function ($join) {
      $join->on('unites.UniteID', '=', 'articles.unite_id');
    })
    ->select('prev_reservation_dets.*','articles.des', 'articles.stock', 'unites.unite')
    ->get();

    return response()->json(['reservation'=>null,'articles_reserved'=>$prev_reservation_det_resultat]);

  }


  public function ViderArticlesReserved($id){

    $success=true;
    $prev_reservation_dets=prev_reservation_det::where('reservation_id','=',$id)->get();
    foreach($prev_reservation_dets as $prev_reservation_det){
      $ReservationDetID=$prev_reservation_det->ReservationDetID;
      $qte=$prev_reservation_det->qte;
      $ArticleID=$prev_reservation_det->article_id;
      $retour_reservation=app('App\Http\Controllers\Divers\generale')->ArticleReserved($ArticleID,-$qte);
      prev_reservation_det::where('ReservationDetID','=',$ReservationDetID)->delete();
    }

    prev_reservation::where('ReservationID','=',$id)->delete();
    return response()->json(['success'=>$success]);
  }

  
  public function viderIntervenantsReserved($id){

    $success=true;
    prev_reservedintervenant_det::where('intreservation_id','=',$id)->delete();
    prev_reservedintervenant::where('IntReservationID','=',$id)->delete();
    
    return response()->json(['success'=>$success]);
  }

  







  
  public function getBonJustTest()
  {
    

      $bonintervenants = di_bon_intervenant::where('bon_id', '=',19)
        ->join('intervenants', function ($join) {
          $join->on('intervenants.IntervenantID', '=', 'di_bon_intervenants.intervenant_id');
        })
        ->join('equi_taches', function ($join) {
          $join->on('equi_taches.TacheID', '=', 'di_bon_intervenants.tache_id');
        })
        ->select(
          'di_bon_intervenants.BonIntervenantID as ReservationIntervenantID',
          'di_bon_intervenants.bon_id as intreservation_id',
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

    $now = Carbon::now();
    return response()->json(['reservedintervenants' => $bonintervenants,'justdatetest'=>$now]);

  }


  
  
  public function getIntReservation($id)
  {
    

    
      $prev_reservedintervenant_dets = prev_reservedintervenant_det::where('intreservation_id', '=',$id)
      ->join('intervenants', function ($join) {
        $join->on('intervenants.IntervenantID', '=', 'prev_reservedintervenant_dets.intervenant_id');
      })
      ->leftjoin('equi_taches', function ($join) {
        $join->on('equi_taches.TacheID', '=', 'prev_reservedintervenant_dets.tache_id');
      })
      ->select(
        'prev_reservedintervenant_dets.ReservationIntervenantID',
        'prev_reservedintervenant_dets.intreservation_id',
        'prev_reservedintervenant_dets.intervenant_id',
        'intervenants.name',
        'prev_reservedintervenant_dets.date1',
        'prev_reservedintervenant_dets.datetime1 as time1',
        'prev_reservedintervenant_dets.date2',
        'prev_reservedintervenant_dets.datetime2 as time2',
        'prev_reservedintervenant_dets.tache_id',
        'equi_taches.tache',
        'prev_reservedintervenant_dets.description'
        
      )
      ->get();

      /*
      $prev_reservedintervenant_for_otp=prev_reservedintervenant::where('IntReservationID','=',$id)->first();
      $otp_id=$prev_reservedintervenant_for_otp->otp_id;
      $prev_otp=prev_otp::where('OtpID','=',$otp_id)->first();
      $date_execution=$prev_otp->date_execution;
      */
      return response()->json(['reservedintervenants' => $prev_reservedintervenant_dets]);

  }


  public function addIntReservation(Request $request,$id){
    
    $success=true;
    $UserID = JWTAuth::user()->id;
    
     //-------- chekck disponibilité des intervenants reservées
     //IsIntReserved
     $allIntervenantsAreDisponible=true; 
     $reservedintervenants=$request->reservedintervenants;
     foreach ($reservedintervenants as $BonIntervenant) {
       //if(!$BonIntervenant['ReservationIntervenantID']){
       $result_disponibilite=$this->IsIntReservedLocal($BonIntervenant['intervenant_id'],null,$BonIntervenant['date1'],$BonIntervenant['time1'],$BonIntervenant['date2'],$BonIntervenant['time2']);
       if(!$result_disponibilite->isDisponible){
         $allIntervenantsAreDisponible=false;
         return response()->json(['success'=>false,'reservedintervenants'=>[],'IntReservationID'=>null]);
        }
      // }
     }
     //########################################################

   



    $reservedintervenants=$request->reservedintervenants;
    $prev_reservedintervenant = prev_reservedintervenant::create([
      'otp_id' => $id,
      'user_id' => $UserID
    ]);
    $IntReservationID = $prev_reservedintervenant->IntReservationID;

    foreach ($reservedintervenants as $BonIntervenant) {

      $date1 = $this->DateFormYMD($BonIntervenant['date1']);
      $time1 = $this->TimeFormHM($BonIntervenant['time1']);
      $date2 = $this->DateFormYMD($BonIntervenant['date2']);
      $time2 = $this->TimeFormHM($BonIntervenant['time2']);

      $datetime1 = $this->combineDateTime($BonIntervenant['date1'], $BonIntervenant['time1']);
      $datetime2 = $this->combineDateTime($BonIntervenant['date2'], $BonIntervenant['time2']);

      prev_reservedintervenant_det::create([
        'intreservation_id' => $IntReservationID,
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
    
    $prev_reservedintervenant_dets=prev_reservedintervenant_det::where('intreservation_id','=',$IntReservationID)
    ->join('intervenants', function ($join) {
      $join->on('intervenants.IntervenantID', '=', 'prev_reservedintervenant_dets.intervenant_id');
    })
    ->leftjoin('equi_taches', function ($join) {
      $join->on('equi_taches.TacheID', '=', 'prev_reservedintervenant_dets.tache_id');
    })
    ->select(
      'prev_reservedintervenant_dets.ReservationIntervenantID',
      'prev_reservedintervenant_dets.intreservation_id',
      'prev_reservedintervenant_dets.intervenant_id',
      'intervenants.name',
      'prev_reservedintervenant_dets.date1',
      'prev_reservedintervenant_dets.datetime1 as time1',
      'prev_reservedintervenant_dets.date2',
      'prev_reservedintervenant_dets.datetime2 as time2',
      'prev_reservedintervenant_dets.tache_id',
      'equi_taches.tache',
      'prev_reservedintervenant_dets.description'
    )
    ->get();


    return response()->json(['success'=>$success,'reservedintervenants'=>$prev_reservedintervenant_dets,'IntReservationID'=>$IntReservationID]);

  }

  public function editIntReservation(Request $request,$id){
    
    $success=true;
    $reservedintervenants=$request->reservedintervenants;
    $DeletedBonIntervenantIDs = $request->DeletedBonIntervenantIDs;
    $UserID = JWTAuth::user()->id;

     

     //-------- chekck disponibilité des intervenants reservées
     //IsIntReserved
     $allIntervenantsAreDisponible=true; 
     $reservedintervenants=$request->reservedintervenants;
     foreach ($reservedintervenants as $BonIntervenant) {
       //if(!$BonIntervenant['ReservationIntervenantID']){
       $result_disponibilite=$this->IsIntReservedLocal($BonIntervenant['intervenant_id'],$BonIntervenant['ReservationIntervenantID'],$BonIntervenant['date1'],$BonIntervenant['time1'],$BonIntervenant['date2'],$BonIntervenant['time2']);
       if(!$result_disponibilite->isDisponible){
         $allIntervenantsAreDisponible=false;
         return response()->json(['success'=>false,'reservedintervenants'=>[],'IntReservationID'=>null]);
        }
      // }
     }
     //########################################################


    
    foreach ($reservedintervenants as $BonIntervenant) {
      
      $date1 = $this->DateFormYMD($BonIntervenant['date1']);
      $time1 = $this->TimeFormHM($BonIntervenant['time1']);
      $date2 = $this->DateFormYMD($BonIntervenant['date2']);
      $time2 = $this->TimeFormHM($BonIntervenant['time2']);
      $datetime1 = $this->combineDateTime($BonIntervenant['date1'], $BonIntervenant['time1']);
      $datetime2 = $this->combineDateTime($BonIntervenant['date2'], $BonIntervenant['time2']);

      if ($BonIntervenant['ReservationIntervenantID'] == null) {
        $prev_reservedintervenant_det = prev_reservedintervenant_det::create([
          'intreservation_id' => $id,
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
        if (!$prev_reservedintervenant_det) {
          $success = false;
        }
      }

      if ($BonIntervenant['ReservationIntervenantID'] > 0) {
        $di_bon_intervenant = prev_reservedintervenant_det::where('ReservationIntervenantID', '=', $BonIntervenant['ReservationIntervenantID'])->update([
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
      $prev_reservedintervenant_det = prev_reservedintervenant_det::where('ReservationIntervenantID', '=', $ID)->delete();
      if (!$prev_reservedintervenant_det) {
        $success = false;
      }
    }
    
    $prev_reservedintervenant_dets=prev_reservedintervenant_det::where('intreservation_id','=',$id)
    ->join('intervenants', function ($join) {
      $join->on('intervenants.IntervenantID', '=', 'prev_reservedintervenant_dets.intervenant_id');
    })
    ->leftjoin('equi_taches', function ($join) {
      $join->on('equi_taches.TacheID', '=', 'prev_reservedintervenant_dets.tache_id');
    })
    ->select(
      'prev_reservedintervenant_dets.ReservationIntervenantID',
      'prev_reservedintervenant_dets.intreservation_id',
      'prev_reservedintervenant_dets.intervenant_id',
      'intervenants.name',
      'prev_reservedintervenant_dets.date1',
      'prev_reservedintervenant_dets.datetime1 as time1',
      'prev_reservedintervenant_dets.date2',
      'prev_reservedintervenant_dets.datetime2 as time2',
      'prev_reservedintervenant_dets.tache_id',
      'equi_taches.tache',
      'prev_reservedintervenant_dets.description'
    )
    ->get();
    return response()->json(['success'=>$success,'reservedintervenants'=>$prev_reservedintervenant_dets,'IntReservationID'=>$id]);
    



    
  }




  public function MethodeGenerale($OtpID,$date,$type){
    
    if($type=='creation'){
      $intervention=$this->GetInterventionByOtpID($OtpID);
      $InterventionID=$intervention->InterventionID;
      $parametrage=$intervention->parametrage;
      
        if($parametrage=='execution'){
          
          //------------------------$this->SetAncienDDR($InterventionID,'ddr'); // ddr // null 
          $this->setDDR($InterventionID,$date,$parametrage); // date // ancien_ddr
          $this->SetAncienDecalage($InterventionID,'decalage'); // decalage // zero 
          $this->SetDecalage($InterventionID,'zero'); // ancien_decalage // zero 
          
          $this->SetDateResultat($InterventionID);
          $this->SetIsPlanified($InterventionID,false);
          $this->SetLastOtp($InterventionID,$OtpID);
          
        }

        if($parametrage=='cloture'){
          $this->SetIsPlanified($InterventionID,false);
        }

        if($parametrage=='planification'){
        
        $prev_interventions = prev_intervention::where('InterventionID','=',$InterventionID)->where('exist','=',1)->first();
        $date_resultat = $prev_interventions->date_resultat;
        $this->setDDR($InterventionID,$date_resultat,'rien');
        $this->SetAncienDecalage($InterventionID,'decalage'); // decalage // zero 
        $this->SetDecalage($InterventionID,'zero'); // ancien_decalage // zero 
        $this->SetDateResultat($InterventionID);
        $this->SetLastOtp($InterventionID,$OtpID);
        
        }
    
      
    }


    if($type=='modification'){
      $intervention=$this->GetInterventionByOtpID($OtpID);
      $InterventionID=$intervention->InterventionID;
      $parametrage=$intervention->parametrage;
      
        if($parametrage=='execution'){
          
          if($this->ifLastOtp($OtpID)){

          $this->setDDR($InterventionID,$date,$parametrage);
          $this->SetDateResultat($InterventionID);
          
          }
        }

        if($parametrage=='cloture'){
           // rien 
          if($this->ifLastOtp($OtpID)){

          $this->setDDR($InterventionID,$date,$parametrage);
          $this->SetDateResultat($InterventionID);

          }

        }

        if($parametrage=='planification'){
          // rien 
        }
    
      
    }


    if($type=='cloture'){
      $intervention=$this->GetInterventionByOtpID($OtpID);
      $InterventionID=$intervention->InterventionID;
      $parametrage=$intervention->parametrage;
      
        if($parametrage=='execution'){
          
          $this->SetIsPlanified($InterventionID,true);
          //-------------------------$this->SetAncienDDR($InterventionID,'ddr'); // ddr // null 
          $this->SetAncienDecalage($InterventionID,'zero'); // decalage // zero 
          
        }

        if($parametrage=='cloture'){
          $this->setDDR($InterventionID,$date,$parametrage);
          $this->SetDecalage($InterventionID,'zero'); // ancien_decalage // zero 
          $this->SetAncienDecalage($InterventionID,'zero'); // decalage // zero 
          $this->SetIsPlanified($InterventionID,true);
          $this->SetLastOtp($InterventionID,$OtpID);
          $this->SetDateResultat($InterventionID);

        }

        if($parametrage=='planification'){
            /*
            $intervention = prev_intervention::where('InterventionID','=',$InterventionID)->first();
            $date_resultat = $intervention->date_resultat;
            
            $this->setDDR($InterventionID,$date_resultat,'rien');
            $this->SetDateResultat($InterventionID);
            $this->SetIsPlanified($InterventionID,true);
            */
            
            // rien 

            $this->SetDecalage($InterventionID,'zero'); // ancien_decalage // zero 
            $this->SetAncienDecalage($InterventionID,'zero'); // decalage // zero 


        }
    
    }



    if($type=='suppression'){

      $intervention=$this->GetInterventionByOtpID($OtpID);
      $InterventionID=$intervention->InterventionID;
      $parametrage=$intervention->parametrage;
      
        if($parametrage=='execution'){
          
          $this->setDDR($InterventionID,'ancien_ddr',$parametrage);
          $this->SetDecalage($InterventionID,'ancien_decalage'); // ancien_decalage // zero 
          $this->SetAncienDecalage($InterventionID,'zero'); // decalage // zero 
          $this->SetDateResultat($InterventionID);
          $this->SetIsPlanified($InterventionID,true);
          $this->unsetLastOtp($InterventionID);
          
        }

        if($parametrage=='cloture'){
          $this->SetIsPlanified($InterventionID,true);
        }

        if($parametrage=='planification'){
          
          if($this->ifLastOtp($OtpID)){
          $this->SetDecalage($InterventionID,'ancien_decalage'); // ancien_decalage // zero 
          $this->SetAncienDecalage($InterventionID,'zero'); // decalage // zero 
          $this->SetDateResultatInverse($InterventionID); // gét ddr le9dima 
          $this->SetDateResultat($InterventionID);
          $this->unsetLastOtp($InterventionID);
              //$this->syestemePlans(); // béch kén fét date mte3ha twalli t9addém marra o5ra
          
          }
        }
    
    }




  }


  public function ReporterPourTester($InterventionID,$decalage){
    prev_intervention::where('InterventionID','=',$InterventionID)->increment('decalage',$decalage);
    $date_resultat=$this->SetDateResultat($InterventionID);
    return $date_resultat;
  }

  public function reporterPlan(Request $request,$id){
    
    $date = $request->input('date');
    $date = Carbon::parse($this->DateFormYMD($date));

    $prev_intervention=prev_intervention::where('InterventionID','=',$id)->first();
    $date_resultat=$prev_intervention->date_resultat;

    $date_resultat= Carbon::parse($this->DateFormYMD($date_resultat));
    $duree=$date_resultat->diffInMinutes($date,false);
    $days=($duree/(60*24));
    $decalage=$days;
    
    prev_intervention::where('InterventionID','=',$id)->increment('decalage',$decalage);
    $date_resultat=$this->SetDateResultat($id);
    //return $date_resultat;
  

  return response()->json(['success'=>true,'date'=>$date_resultat]);


  }


  public function syestemePlans(){
  
    $now = Carbon::now();
    $now=Carbon::parse("2020-08-07");
    $prev_interventions = prev_intervention::where('parametrage','=','planification')->where('exist','=',1)->get();
    foreach ($prev_interventions as $intervention) {
      $date_resultat = $intervention->date_resultat;
      $InterventionID = $intervention->InterventionID;
      if ($now > $date_resultat) {
        
        while($now>$date_resultat){
        $this->setDDR($InterventionID,$date_resultat,'rien');
        $this->SetDecalage($InterventionID,'zero'); // ancien_decalage // zero 
        $this->SetAncienDecalage($InterventionID,'zero'); // decalage // zero 
        $date_resultat=$this->SetDateResultat($InterventionID);
        $this->SetIsPlanified($InterventionID,true);
        }
        

      }
    }

  }

  public function isExistOtpEnCoursOfThisInterventionID($InterventionID){
    $prev_otps=prev_otp::where('intervention_id','=',$InterventionID)->where('exist','=',1)->where('statut','=','enCours')->get();
    if(count($prev_otps)>0){ return true;}else{return false;}
  }



  public function GetInterventionByOtpID($OtpID){
    $prev_otp=prev_otp::where('OtpID','=',$OtpID)->first();
    $InterventionID=$prev_otp->intervention_id;
    return prev_intervention::where('InterventionID','=',$InterventionID)->first();
  }

  public function SetDateResultat($InterventionID){
    $prev_intervention=prev_intervention::where('InterventionID','=',$InterventionID)->first();
    $ddr=$prev_intervention->ddr;
    $frequence=$prev_intervention->frequence;
    $decalage=$prev_intervention->decalage;
    $nv_date_resultat = Carbon::parse($ddr)->addDays($frequence*7)->addDays($decalage);
    prev_intervention::where('InterventionID','=',$InterventionID)->update(['date_resultat'=>$nv_date_resultat]);
    return $nv_date_resultat;
  }

  public function SetAncienDDR($InterventionID,$param){
    if($param=='null'){
      return prev_intervention::where('InterventionID','=',$InterventionID)->update(['ancien_ddr'=>null]);
    }

    if($param=='ddr'){
      $prev_intervention=prev_intervention::where('InterventionID','=',$InterventionID)->first();
      $ddr=$prev_intervention->ddr;
      $ancien_ddr=$prev_intervention->ancien_ddr;
      if($ancien_ddr==$ddr){
        return 'meme';
      }else{
        return prev_intervention::where('InterventionID','=',$InterventionID)->update(['ancien_ddr'=>$ddr]);
      }
    }
  }

  
  public function SetDateResultatInverse($InterventionID){ // hiya pratiquement setAncienDDR elli kén 9bal l'execution 

    $prev_intervention=prev_intervention::where('InterventionID','=',$InterventionID)->first();
    $ddr=$prev_intervention->ddr;
    $date_resultat=$prev_intervention->date_resultat;
    $frequence=$prev_intervention->frequence;
    $decalage=$prev_intervention->decalage;
    $nv_ddr = Carbon::parse($ddr)->addDays(-($frequence*7))->addDays(-$decalage);
    //prev_intervention::where('InterventionID','=',$InterventionID)->update(['date_resultat'=>$ddr]);
    prev_intervention::where('InterventionID','=',$InterventionID)->update(['ddr'=>$nv_ddr]);
   
  }



  public function GetAncienDDR($InterventionID,$parametrage){
  if($parametrage=='execution'){

    $thisOtp=prev_intervention::where('InterventionID','=',$InterventionID)->first();
    $thisOtpID=$thisOtp->lastOtp;
    
    $prev_otp=prev_otp::where('intervention_id','=',$InterventionID)->where('OtpID','<>',$thisOtpID)->where('exist','=',1)
    ->orderBy('created_at', 'desc')->first();
  
   if($prev_otp){
    $ddr=$prev_otp->date_execution;
    return $ddr;
   }else{
    return NULL;
   }

  }

  }


  public function TestGetAncienDDR($InterventionID){
     //return response()->json($this->GetAncienDDR($InterventionID,'execution'));
     //$this->setDDR($InterventionID,'ancien_ddr','execution');
     //return response()->json($this->unsetLastOtp($InterventionID));
     return response()->json($this->syestemePlans());

    // $this->SetDateResultatInverse($InterventionID);

    /*
    $prev_interventions = prev_intervention::where('InterventionID','=',$InterventionID)->where('exist','=',1)->first();
    $date_resultat = $prev_interventions->date_resultat;
    $this->setDDR($InterventionID,$date_resultat,'rien');
    $this->SetDateResultat($InterventionID);
    $this->SetLastOtp($InterventionID,10);
    */

    //$this->ReporterPourTester($InterventionID,2);
  }



  public function SetAncienDecalage($InterventionID,$param){
    if($param=='zero'){
      return prev_intervention::where('InterventionID','=',$InterventionID)->update(['ancien_decalage'=>0]);
    }

    if($param=='decalage'){
      $prev_intervention=prev_intervention::where('InterventionID','=',$InterventionID)->first();
      $decalage=$prev_intervention->decalage;
      $ancien_decalage=$prev_intervention->ancien_decalage;
      if($ancien_decalage==$decalage){
        return 'meme';
      }else{
        return prev_intervention::where('InterventionID','=',$InterventionID)->update(['ancien_decalage'=>$decalage]);
      }
    }
  }

  

  public function SetDecalage($InterventionID,$param){
    if($param=='zero'){
      return prev_intervention::where('InterventionID','=',$InterventionID)->update(['decalage'=>0]);
    }

    if($param=='ancien_decalage'){
      $prev_intervention=prev_intervention::where('InterventionID','=',$InterventionID)->first();
      $decalage=$prev_intervention->decalage;
      $ancien_decalage=$prev_intervention->ancien_decalage;
      if($ancien_decalage==$decalage){
        return 'meme';
      }else{
        return prev_intervention::where('InterventionID','=',$InterventionID)->update(['decalage'=>$ancien_decalage]);
      }
    }
  }

  public function SetIsPlanified($InterventionID,$bool){
    return prev_intervention::where('InterventionID','=',$InterventionID)->update(['isPlanified'=>$bool]);
  }

  public function SetLastOtp($InterventionID,$OtpID){
    return prev_intervention::where('InterventionID','=',$InterventionID)->update(['lastOtp'=>$OtpID]);
  }

  public function unsetLastOtp($InterventionID){
   $thisOtp=prev_intervention::where('InterventionID','=',$InterventionID)->first();
   $thisOtpID=$thisOtp->lastOtp;
   
   $prev_otp=prev_otp::where('intervention_id','=',$InterventionID)->where('OtpID','<>',$thisOtpID)->where('exist','=',1)
   ->orderBy('created_at', 'desc')->first();
   if($prev_otp){
    $OtpID=$prev_otp->OtpID;
    return prev_intervention::where('InterventionID','=',$InterventionID)->update(['lastOtp'=>$OtpID]);
   }else{
    return prev_intervention::where('InterventionID','=',$InterventionID)->update(['lastOtp'=>null]);
   }
  }

  public function ifLastOtp($OtpID){
    $prev_otp=prev_otp::where('OtpID','=',$OtpID)->first();
    $InterventionID=$prev_otp->intervention_id;
    $prev_intervention=prev_intervention::where('InterventionID','=',$InterventionID)->first();
    $lastOtp=$prev_intervention->lastOtp;
    if($lastOtp==$OtpID || $OtpID==NULL){
      return true;
    }else{
      return false;
    }
  }



  
  public function setDDR($InterventionID,$date,$parametrage){

    // Ancien Fonction 
    /*
    $prev_intervention=prev_intervention::where('InterventionID','=',$InterventionID)->first();
    $ddr=$prev_intervention->ddr;
    $ancien_ddr=$prev_intervention->ancien_ddr;

    if($date=='ancien_ddr'){
      return prev_intervention::where('InterventionID','=',$InterventionID)->update(['ddr'=>$ancien_ddr]);
    }else{
    if($ddr==$date){
     return 'meme';
    }else{
      return prev_intervention::where('InterventionID','=',$InterventionID)->update(['ddr'=>$date]);
    }
    }
    */

    // Nouveau Fonction 
    $prev_intervention=prev_intervention::where('InterventionID','=',$InterventionID)->first();
    $ddr=$prev_intervention->ddr;

    if($date=='ancien_ddr'){
      $AncienDDR=$this->GetAncienDDR($InterventionID,$parametrage);
      if($AncienDDR){
        return prev_intervention::where('InterventionID','=',$InterventionID)->update(['ddr'=>$AncienDDR]);
      }else{
        
        $prev_intervention=prev_intervention::where('InterventionID','=',$InterventionID)->first();
        $ddr=$prev_intervention->ddr;
        $date_resultat=$prev_intervention->date_resultat;
        $frequence=$prev_intervention->frequence;
        //$ancien_decalage=$prev_intervention->ancien_decalage;
        $nv_ddr = Carbon::parse($ddr)->addDays(-($frequence*7));//->addDays(-$ancien_decalage);
        //prev_intervention::where('InterventionID','=',$InterventionID)->update(['date_resultat'=>$ddr]);
        prev_intervention::where('InterventionID','=',$InterventionID)->update(['ddr'=>$nv_ddr]);
        return $nv_ddr;



        // ancien 
        //return $ddr;
      }
     
    }else{
    if($ddr==$date){
     return 'meme';
    }else{
      return prev_intervention::where('InterventionID','=',$InterventionID)->update(['ddr'=>$date]);
    }
    }


  }







  //////////////////////////  Calender 


  
  public function getEventsIPs(Request $request){

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
    /*
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
*/

      $ips = prev_intervention::
      where('prev_interventions.exist','=',true)
    ->whereDate('prev_interventions.date_resultat', '>=', $datestart)
    ->whereDate('prev_interventions.date_resultat', '<=', $dateend)
    //->whereIn('prev_interventions.type', $type)
    ->where('prev_interventions.isPlanified','=',1)

    ->join('equi_taches', function ($join) {
      $join->on('equi_taches.TacheID', '=', 'prev_interventions.tache_id');
    })
    ->select('prev_interventions.*','equi_taches.tache')
    ->get();
    return response()->json($ips);

  }


  // #################################




  public function InterventionIsCoupleEquipementTacheExist($equipement_id,$tache_id){
    $prev_interventions=prev_intervention::where('equipement_id','=',$equipement_id)->where('tache_id','=',$tache_id)->where('exist','=',1)->get();
    if(count($prev_interventions)>0){ return true;}else{ return false;}
  }

  public function IsIntReserved(Request $request){

    $IntervenantID=$request->input('IntervenantID');
    $date1=$request->input('date1');
    $date2=$request->input('date2');
    $time1=$request->input('time1');
    $time2=$request->input('time2');
    $ReservationIntervenantID=$request->input('ReservationIntervenantID');


    
    $date1 = $this->DateFormYMD($date1);
    $time1 = $this->TimeFormHM($time1);
    $date2 = $this->DateFormYMD($date2);
    $time2 = $this->TimeFormHM($time2);
    

    $datetime1 = $this->combineDateTime($date1,$time1);
    $datetime2 = $this->combineDateTime($date2,$time2);
  
  
    $prev_reservedintervenant_dets=prev_reservedintervenant_det::
          where('intervenant_id','=',$IntervenantID)
          ->where('ReservationIntervenantID','<>',$ReservationIntervenantID)
          ->Where(function ($query) use($datetime1,$datetime2){
            return $query

            ->Where(function ($query) use($datetime1,$datetime2){
              return $query->where('prev_reservedintervenant_dets.datetime1','>',$datetime1)
                           ->where('prev_reservedintervenant_dets.datetime1','<',$datetime2);
            })
            ->orWhere(function ($query) use($datetime1,$datetime2){
              return $query->where('prev_reservedintervenant_dets.datetime2','>',$datetime1)
                           ->where('prev_reservedintervenant_dets.datetime2','<',$datetime2);
            })
            
            ->orWhere(function ($query) use($datetime1,$datetime2){
              //return $query->WhereRaw('? < prev_reservedintervenant_dets.datetime1', [$datetime1])
                           //->WhereRaw('? > prev_reservedintervenant_dets.datetime2', [$datetime2]);
                return $query->where('prev_reservedintervenant_dets.datetime1','>=',$datetime1)
                           ->where('prev_reservedintervenant_dets.datetime2','<=',$datetime2);
            })
            ->orWhere(function ($query) use($datetime1,$datetime2){
              //return $query->WhereRaw('? > prev_reservedintervenant_dets.datetime1', [$datetime1])
                           //->WhereRaw('? < prev_reservedintervenant_dets.datetime2', [$datetime2]);
                return $query->where('prev_reservedintervenant_dets.datetime1','<=',$datetime1)
                           ->where('prev_reservedintervenant_dets.datetime2','>=',$datetime2);
            });
            
          })
        ->join('intervenants', function ($join) {
            $join->on('intervenants.IntervenantID', '=', 'prev_reservedintervenant_dets.intervenant_id');
          })
          ->join('prev_reservedintervenants', function ($join) {
            $join->on('prev_reservedintervenants.IntReservationID', '=', 'prev_reservedintervenant_dets.intreservation_id');
          })
          ->join('users', function ($join) {
            $join->on('users.id', '=', 'prev_reservedintervenants.user_id');
          })

        ->select('prev_reservedintervenant_dets.*','prev_reservedintervenants.updated_at as date_reservation','intervenants.*','users.name as reserveur') 
        ->get();
    
    $IsIntReserved=false;
    if(count($prev_reservedintervenant_dets)==0){$IsIntReserved=true;}
    return response()->json(['IsIntReserved'=>$IsIntReserved,'DateTimeList'=>$prev_reservedintervenant_dets]);
     
  }


  public function IsIntReservedLocal($IntervenantID,$ReservationIntervenantID,$date1,$time1,$date2,$time2){

    $date1 = $this->DateFormYMD($date1);
    $time1 = $this->TimeFormHM($time1);
    $date2 = $this->DateFormYMD($date2);
    $time2 = $this->TimeFormHM($time2);
    
    $datetime1 = $this->combineDateTime($date1,$time1);
    $datetime2 = $this->combineDateTime($date2,$time2);
    /*
    $datetime1M1 = Carbon::parse($datetime1)->format('Y-m-d H:i');
    $datetime1P1 = Carbon::parse($datetime1)->format('Y-m-d H:i');
    $datetime2M1 = Carbon::parse($datetime2)->format('Y-m-d H:i');
    $datetime2P1 = Carbon::parse($datetime2)->format('Y-m-d H:i');
*/
  
    $prev_reservedintervenant_dets=prev_reservedintervenant_det::
          where('intervenant_id','=',$IntervenantID)
          ->where('ReservationIntervenantID','<>',$ReservationIntervenantID)

          /*
          ->Where(function ($query) use($datetime1,$datetime2,$datetime1M1,$datetime2M1,$datetime1P1,$datetime2P1){
            return $query
        ->whereBetween('datetime1', [$datetime1M1, $datetime1P1]) 
        ->orWhereBetween('datetime2', [$datetime1M1, $datetime1P1]) 
        ->orWhereRaw('? BETWEEN datetime1 and datetime2', [$datetime1]) 
        ->orWhereRaw('? BETWEEN datetime1 and datetime2', [$datetime2]);
          })
          */

          ->Where(function ($query) use($datetime1,$datetime2){
            return $query

            ->Where(function ($query) use($datetime1,$datetime2){
              return $query->where('prev_reservedintervenant_dets.datetime1','>',$datetime1)
                           ->where('prev_reservedintervenant_dets.datetime1','<',$datetime2);
            })
            ->orWhere(function ($query) use($datetime1,$datetime2){
              return $query->where('prev_reservedintervenant_dets.datetime2','>',$datetime1)
                           ->where('prev_reservedintervenant_dets.datetime2','<',$datetime2);
            })
            
            ->orWhere(function ($query) use($datetime1,$datetime2){
              //return $query->WhereRaw('? < prev_reservedintervenant_dets.datetime1', [$datetime1])
                           //->WhereRaw('? > prev_reservedintervenant_dets.datetime2', [$datetime2]);
                return $query->where('prev_reservedintervenant_dets.datetime1','>=',$datetime1)
                           ->where('prev_reservedintervenant_dets.datetime2','<=',$datetime2);
            })
            ->orWhere(function ($query) use($datetime1,$datetime2){
              //return $query->WhereRaw('? > prev_reservedintervenant_dets.datetime1', [$datetime1])
                           //->WhereRaw('? < prev_reservedintervenant_dets.datetime2', [$datetime2]);
                return $query->where('prev_reservedintervenant_dets.datetime1','<=',$datetime1)
                           ->where('prev_reservedintervenant_dets.datetime2','>=',$datetime2);
            });
            
          })

        ->join('intervenants', function ($join) {
            $join->on('intervenants.IntervenantID', '=', 'prev_reservedintervenant_dets.intervenant_id');
          })
          ->join('prev_reservedintervenants', function ($join) {
            $join->on('prev_reservedintervenants.IntReservationID', '=', 'prev_reservedintervenant_dets.intreservation_id');
          })
          ->join('users', function ($join) {
            $join->on('users.id', '=', 'prev_reservedintervenants.user_id');
          })

        ->select('prev_reservedintervenant_dets.*','prev_reservedintervenants.updated_at as date_reservation','intervenants.*','users.name as reserveur') 
        ->get();
    
    $IsIntReserved=false;
    if(count($prev_reservedintervenant_dets)==0){$IsIntReserved=true;}

    $result=new \stdClass();
    $result->isDisponible=$IsIntReserved;
    $result->DateTimeList=$prev_reservedintervenant_dets;

    return $result;
    //return ['IsIntReserved'=>$IsIntReserved,'DateTimeList'=>$prev_reservedintervenant_dets] ;
     
  }


  


}
