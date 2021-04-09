<?php

namespace App\Http\Controllers\Achat\da;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\Achat\da;
use App\Model\Achat\daarticle;
use App\Model\Magasin\article;
use App\Model\Achat\rart;
use App\Model\Achat\fournisseur;
use App\User;
use Carbon\Carbon;
use JWTFactory;
use JWTAuth;


class actionController extends Controller
{
    
    public function __construct()
    { 
        
      $this->middleware('responsableachat')->only('actionresponsableachat');   
     // $this->middleware('admin')->only('actionadmin');   
      // $this->middleware('utilisateur')->only('actionutilisateur');   
      
    }


    public function actionresponsableachat(Request $request,$id){
      $poste='ResponsableAchat';

      $actions=$request->all();
      $action=$actions['action'];
      $message=$actions['message'];
      $delaidereportation=$actions['delaidereportation'];

      return $this->changementStatut($poste,$action,$id,$message,$delaidereportation);


     }

    
    public function actionadmin(Request $request,$id){
      $poste='admin';

      $actions=$request->all();
      $action=$actions['action'];
      $message=$actions['message'];
      $delaidereportation=$actions['delaidereportation'];

      return $this->changementStatut($poste,$action,$id,$message,$delaidereportation);

     }



    //actionutilisateur


    public function changementStatut($poste,$action,$da_id,$message,$delaidereportation){

      $statut=da::select('statut')->where('id','=',$da_id)->first();
      $statut=$statut->statut;

      //====================================================> ResponsableAchat
      if($poste=='ResponsableAchat'){

       $ActionStatut=new \stdClass();
       $ActionStatut->confirmer='confirme';
       $ActionStatut->rejeter='rejete';
       $ActionStatut->reporter='reporte';
       $ActionStatut->transferer='enattente';
       $ActionStatut->rereporter='reporte';
       $ActionStatut->reprise='reencours';
       $ActionStatut->remise='reencours';

     if(
        
        (  $action=='confirmer' && ( $statut=='encours' || $statut=='reencours' || $statut=='reenvoye'  ) ) 
        ||
        (  $action=='rejeter'   && ( $statut=='encours' || $statut=='reencours' || $statut=='reenvoye' ||  $statut=='confirmeParAdmin' ) ) 
        ||
        (  $action=='reporter'   && ( $statut=='encours' || $statut=='reencours' || $statut=='reenvoye' ||  $statut=='confirmeParAdmin' ) ) 
        ||
        (  $action=='rereporter'   &&  $statut=='reporte'  ) 
        ||
        (  $action=='transferer'   && ( $statut=='encours' || $statut=='reencours' || $statut=='reenvoye' ) )
        ||
        (  $action=='reprise'   && ( $statut=='enattente' || $statut=='reenattenteParAdmin' )  )
        || 
        (  $action=='remise'   && ( $statut=='confirme' || $statut=='reporte' || $statut=='confirmeParAdmin' )  )
    
      ){
        $retourStatut=da::where('id','=',$da_id)->update(array('statut' => $ActionStatut->$action ));
        if($retourStatut){
        $retourRart=rart::create([
        'user_id'=>JWTAuth::user()->id,
        'da_id'=>$da_id,
        'vu'=>0,
        'statut'=>$ActionStatut->$action ,
        'message'=>$message,
        'delaidereportation'=>$delaidereportation
        ]);
        }
        
        //return response()->json($retourRart);
        return response()->json( $this->getDaAfterAction($da_id) ) ;
      }





    }


     //====================================================> admin


     if($poste=='admin'){

      $ActionStatut=new \stdClass();
      $ActionStatut->confirmer='confirmeParAdmin';
      $ActionStatut->rejeter='rejete';
      $ActionStatut->reporter='reporteParAdmin';
      $ActionStatut->rereporter='reporteParAdmin';
      $ActionStatut->remise='reenattenteParAdmin';
      $ActionStatut->retransferer='reencours';
      
      // if retransferer ...
    if(

       (  $action=='confirmer' && ( $statut=='reenattenteParAdmin' || $statut=='enattente' ) ) 
       ||
       (  $action=='rejeter'   && ( $statut=='reenattenteParAdmin' || $statut=='enattente' ) ) 
       ||
       (  $action=='reporter'   && ( $statut=='reenattenteParAdmin' || $statut=='enattente' ) ) 
       ||
       (  $action=='rereporter'   &&  $statut=='reporteParAdmin'  ) 
       ||
       (  $action=='remise'   && ( $statut=='confirmeParAdmin' || $statut=='reporteParAdmin' )  )
       || 
       (  $action=='retransferer'   && ( $statut=='enattente' || $statut=='reenattenteParAdmin' )  )

     ){
       $retourStatut=da::where('id','=',$da_id)->update(array('statut' => $ActionStatut->$action ));
       if($retourStatut){
       $retourRart=rart::create([
       'user_id'=>JWTAuth::user()->id,
       'da_id'=>$da_id,
       'vu'=>0,
       'statut'=>$ActionStatut->$action ,
       'message'=>$message,
       'delaidereportation'=>$delaidereportation
       ]);
       }
      // return response()->json($retourRart);
       return response()->json( $this->getDaAfterAction($da_id) ) ;
     }

   }


  }



public function getDaAfterAction($DaID){

  $returnArr=Array();


  $das = da::where('id','=',$DaID)->get();
  foreach($das as $da){

  $DemandeurName=User::select('name')->where('id','=',$da['user_id'])->get();
  $da->user=$DemandeurName[0]['name'];//['name'];
  $da->open=0;
  $da->openStatus=0;
   //delaiReste
   $da_delai_created=da::select('delai','created_at')->where('id','=',$da['id'])->first();
   $delaiPourReste=$da_delai_created->delai;
   $created_at=$da_delai_created->created_at;
   $date_de_creation = Carbon::parse($created_at);
   $date_de_creation->hour(0)->minute(0)->second(0);
   $today = Carbon::now();
   $today->hour(0)->minute(0)->second(0);
   $diff=$date_de_creation->diffInDays($today,false);
   $reste_delai= ( $delaiPourReste - $diff )  ; 
   
   $da->restededelai=$reste_delai;
   //##########
   

  $daDet=new \stdClass();
  $daDet->da=$da;
  $arrDetDaWithDetArticle=Array();
  $daDetWithoutDetArticles=daarticle::where('da_id','=',$da['id'])->get();
  foreach($daDetWithoutDetArticles as $daarticle){
    $daarticle_article=new \stdClass();
    $daarticle_article->daarticle=$daarticle;
    $daarticle_article->article=article::where('ArticleID','=',$daarticle['article_id'])->get(); 
    $daarticle_article->article[1]=fournisseur::select('nom')->where('id','=',$daarticle_article->article[0]['fournisseur_id'])->first();
    array_push($arrDetDaWithDetArticle,$daarticle_article);
  }

  $daDet->detDa=$arrDetDaWithDetArticle;

  $daDet->status=$this->getStatusWithName($da['id']); //rart::where('da_id','=',$da['id'])->get();


  array_push($returnArr,$daDet);

  }
//return response()->json($returnArr);
return $returnArr[0];

}

/*
  // 5thitha men daControler 
  public function getDaAfterAction($DaID){
    $returnArr=Array();
  
  
    $das = da::where('id','=',$DaID)->get();

    foreach($das as $da){
  
    $DemandeurName=User::select('name')->where('id','=',$da['user_id'])->get();
    $da->user=$DemandeurName[0]['name'];//['name'];
    $da->open=0;
    $da->openStatus=0;
    
  
    $daDet=new \stdClass();
    $daDet->da=$da;
    $arrDetDaWithDetArticle=Array();
    $daDetWithoutDetArticles=daarticle::where('da_id','=',$da['id'])->get();
    foreach($daDetWithoutDetArticles as $daarticle){
      $daarticle_article=new \stdClass();
      $daarticle_article->daarticle=$daarticle;
      $daarticle_article->article=article::where('ArticleID','=',$daarticle['article_id'])->get(); 
      array_push($arrDetDaWithDetArticle,$daarticle_article);
    }
  
    $daDet->detDa=$arrDetDaWithDetArticle;
  
    $daDet->status=$this->getStatusWithName($da['id']); //rart::where('da_id','=',$da['id'])->get();
  
  
    array_push($returnArr,$daDet);
  
    }
  return $returnArr[0];
  }
*/

  // 5thitha men daControler 
  public function getStatusWithName($DaID){


    $status=rart::select('user_id','statut','message','delaidereportation','created_at')->where('da_id','=',$DaID)->get();
    $i=0;
    foreach($status as $statut){
      $status[$i]['user_id']=User::select('name')->where('id','=',$status[$i]['user_id'])->first();
      $i=$i+1;
    }
    
    //return $status;
    //$ret="bannour <br> sami <br> test";
    return $status;
  
  }



  public function checkDasReportees(){
    $arr=Array();
    $listeDasReportees=da::select('id','statut')->where('statut','=','reporte')->orWhere('statut', '=','reporteParAdmin')->get();
    foreach($listeDasReportees as $daReporte){
      $DaID=$daReporte['id']; $statut=$daReporte['statut'];
      //return response()->json($listeDasReportees);
      $rartDet=rart::select('delaidereportation','created_at')
            ->where('da_id','=',$DaID)->where('statut','=',$statut)
            ->orderBy('created_at', 'desc')->first();
      $dateDeReportation=$rartDet->created_at;
      $delaiDeReportation=$rartDet->delaidereportation;

      return $this->RemiseEncoursParLeSysteme($DaID,$dateDeReportation,$delaiDeReportation,$statut);

    }
    //return response()->json($arrTest);
  }

  public function RemiseEncoursParLeSysteme($DaID,$dateDeReportation,$delaiDeReportation,$statut){

    $ts = strtotime($dateDeReportation);
    $JourAjoutees = $delaiDeReportation * ( 3600 * 24 ) ; // nombre de secondes dans une journée
    $ts += $JourAjoutees;
    $dateDeRemiseEncours=date('Y-m-d', $ts);
    $dateToday=date("Y-m-d");
    //$cestletemps = strtotime($dateDeRemiseEncours) <= strtotime($dateToday) ; 
    //return response()->json( $dateDeRemiseEncours);

    //return response()->json();
    if( strtotime($dateDeRemiseEncours) <= strtotime($dateToday) ){
      
      //return response()->json( 'zab chnowa taw ');
      

      if($statut=='reporte'){$statutToInsert='reencours';}
      if($statut=='reporteParAdmin'){$statutToInsert='reenattenteParAdmin';}
      
     

      if($statutToInsert){
      $retourUpdateStatut = da::where('id','=',$DaID)->update(array('statut' =>$statutToInsert ));
      if($retourUpdateStatut){
      $retourRart=rart::create([
      'user_id'=>1,
      'da_id'=>$DaID,
      'vu'=>0,
      'delaidereportation'=>0,
      'statut'=>$statutToInsert

      ]);
      }
      }

      //return response()->json($retourRart);
    }

  }






}
