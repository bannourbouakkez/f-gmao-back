<?php


namespace App\Http\Controllers\Achat\da;
use DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\Achat\da;
use App\Model\Achat\daarticle;
use App\Model\Magasin\article;
use App\Model\Achat\fournisseur;
use App\Model\Achat\rart;
use App\Model\Auth\post;
use App\Model\Auth\user_post;
use App\User;
use Carbon\Carbon;
use JWTFactory;
use JWTAuth;
use App\Notifications\NewDa;
use App\Notifications\Alerte;



class daController extends Controller
{


  // Nv nvGetDa ba3d ma kammalt el magasin w rja3t bil 5edbra 
    public function nvGetDa($DaID){

       $da=da::where('das.id','=',$DaID)
       ->join('users', function ($join) {
        $join->on('users.id', '=', 'das.user_id');
       })
       ->select('das.*','users.name')
       ->first();
       
       $daarticle=daarticle::where('daarticles.da_id','=',$DaID)
       ->join('articles', function ($join) {
        $join->on('articles.ArticleID', '=', 'daarticles.article_id');
       })
       ->select('daarticles.*','articles.des')
       ->get();

       $daarticle_cmde=daarticle::where('daarticles.da_id','=',$DaID)
       ->join('cmd_daarticles', function ($join) {
        $join->on('cmd_daarticles.daarticle_id', '=', 'daarticles.id');
       })
       ->join('cmds', function ($join) {
        $join->on('cmds.CommandeID', '=', 'cmd_daarticles.commande_id')
        ->where('cmds.exist','=',1);
      }) // tasli7
      
       ->select('cmd_daarticles.*')
       ->get();

       return response()->json(['da'=>$da,'daarticle'=>$daarticle,'daarticle_cmde'=>$daarticle_cmde]);   

    }

  //###################
    public function addda(Request $request){
        $success=true;
        $msg="Demande d'achat est ajoutée avec succées .";

        //id,ref,user_id,delai,remarques,statut,exist,created_at,updated_at
        $daorig=$request->except('DaArticles');
        $refIsExist=da::where('ref','=',$daorig['ref'])->count();
        if($refIsExist){$random=rand(10000000,99999999);}
        $da=new da(); 
        if($refIsExist){
        $random=rand(10000000,99999999);   
        $da->ref=$random;
        }else{ $da->ref=$daorig['ref']; }
        $da->user_id=JWTAuth::user()->id;
        $da->delai=$daorig['delai'];
        $da->remarques=$daorig['remarques'];
        $da->statut="encours";
        $da->exist=1; 
        $da->save();
        $DaID=$da->id;

        // notification 
        $user_tn=User::find(2);
        $user_a=User::find(JWTAuth::user()->id);
        $da_obj=da::find($DaID);

        if($da_obj->delai==1){
          //$user_tn->notify(new Alerte());
        }else{
          //$user_tn->notify(new NewDa($da_obj,$user_a));
        }
        
        $retourRart=rart::create([
          'user_id'=>JWTAuth::user()->id,
          'da_id'=>$DaID,
          'vu'=>0,
          'statut'=>'encours' ,
          'message'=>'',
          'delaidereportation'=>0
          ]);


        $daArticles = $request->only('DaArticles');
        $daArticles=$daArticles['DaArticles'];

       
        //id,da_id,article_id,qte,motif,created_at,updated_at
        foreach ($daArticles as $key => $value) {
              $daArticle=new daarticle();
              $daArticle->da_id=$DaID;
              $daArticle->article_id=$value['ArticleID'];
              $daArticle->qte=$value['qte'];
              $daArticle->motif=$value['motif'];
              $daArticle->save();
            }

            // notification 


        return response()->json(['success'=>$success,'msg'=>$msg,'DaID'=>$DaID]);
    }


    public function showda($id){
       //id,ref,user_id,delai,remarques,statut,exist,created_at,updated_at
       //id,da_id,article_id,qte,motif,created_at,updated_at

        $da= da::find($id);

        $UserID=JWTAuth::user()->id;
        $DaUserID=$da->user_id;
        if($DaUserID!=$UserID ){return response()->json(false);}

        $da->DeletedDaArticleIDs="";        
        $daArticles=daarticle::where('da_id','=',$id)->get();

        $allDaArticles=Array();
         
         foreach ($daArticles as $obj) {
            $newDaArticle=(object)[];
            $newDaArticle->DaArticleID=$obj->id;
            $newDaArticle->DaID=$obj->da_id;
            $newDaArticle->ArticleID=$obj->article_id;
            $newDaArticle->des=article::find($obj->article_id)->des;
            $newDaArticle->unite=article::find($obj->article_id)->unite;
            $newDaArticle->qte=$obj->qte;
            $newDaArticle->motif=$obj->motif;

            $allDaArticles[count($allDaArticles)]=$newDaArticle;
        }
        
         $da->DaArticles=$allDaArticles;

         return response()->json($da);
         
    }

    
    public function updateDa(Request $request){
      $success=true;
      $msg="Demande d'achat est modifiée avec succées .";

      $UserID=JWTAuth::user()->id;
      $statuPourVerifier=$request->input('statut');
      $DaUserID=$request->input('user_id');
      if(  ( $statuPourVerifier!='rejete' && $statuPourVerifier!='rejeteParAdmin' ) || $DaUserID!=$UserID ){return response()->json(false);}
        
        $success=true;
        $DaID=$request->input('id');
        $arrIDsToDelete=explode(',',$request->input('DeletedDaArticleIDs'),-1);
        $daorig=$request->except('DaArticles');
        
        $da = new \stdClass();
        $da->delai=$daorig['delai'];
        $da->remarques=$daorig['remarques'];
        $da->statut='reenvoye';

        $da->created_at=Carbon::now();
        $da->updated_at=Carbon::now();
        //$da->timestamps=false;
        $da=(array)$da;

        /*
        created_at = Carbon::today();
        ['timestamps' => false]
        Carbon::today()->toDateTimeString()
        */

        $DaUpdated =  da::where('id','=', $DaID)->update($da);
        
        if($DaUpdated){ // retour 1 en success 
           
          $retourRart=rart::create([
            'user_id'=>JWTAuth::user()->id,
            'da_id'=>$DaID,
            'vu'=>0,
            'statut'=>'reenvoye' ,
            'message'=>'',
            'delaidereportation'=>0
            ]);

            $daArticles = $request->only('DaArticles');
            $daArticles=$daArticles['DaArticles'];          
          
          foreach ($arrIDsToDelete as $value) {
              $dtetedID=daarticle::find($value)->delete();
              if(!$dtetedID){$success=false;}
          }
          
         foreach ($daArticles as $key => $value) {
                
                if($value['DaArticleID']){
                 // update //id,da_id,article_id,qte,motif,created_at,updated_at
                 $ar = new \stdClass();
                 $ar->article_id=$value['ArticleID'];
                 $ar->qte=$value['qte'];
                 $ar->motif=$value['motif'];
                 $ar=(array)$ar;
                 $arUpdated =  daarticle::where('id','=', $value['DaArticleID'])->update($ar);
                 if(!$arUpdated){$success=false;}
                  
                }else{
                // create //id,da_id,article_id,qte,motif,created_at,updated_at
                $ar = new \stdClass();
                $ar->da_id=$DaID;
                $ar->article_id=$value['ArticleID'];
                $ar->qte=$value['qte'];
                $ar->motif=$value['motif'];
                $ar=(array)$ar;
                $arCreated=daarticle::create($ar);
                if(!$arCreated){$success=false;}
                }
              }
        }
 
      return response()->json(['success'=>$success,'msg'=>$msg]);
    }



//==================================================================> Gerer 

public function indexgererda(Request $request){
  $returnArr=Array();
  $start = new Carbon('first day of last month');
  $isAdmin=false;
  $isResponsableAchat=false;
  $UserID=JWTAuth::user()->id;
  $user_post_ids=user_post::select('post_id')->where('user_id','=',$UserID)->get();
  foreach($user_post_ids as $user_post_id){
      $user_post_id_v= $user_post_id['post_id'];
      $post=post::select('post')->where('PostID','=',$user_post_id_v)->first();
      $post=$post->post;
      if($post=='ResponsableAchat'){$isResponsableAchat=true;}
      if($post=='Admin'){$isAdmin=true;}
  }

  $das = da::
  Where('created_at','>=',$start)
  ->Where(function ($query) use($isResponsableAchat,$isAdmin,$UserID){
          if($isResponsableAchat==true){
          return $query
          ->where('statut','=', 'encours')
          ->orWhere('statut','=', 'reenvoye')
          ->orWhere('statut','=', 'reencours')
          ->orWhere('statut','=', 'enattente')
          ->orWhere('statut','=', 'reenattenteParAdmin');
          }else if($isAdmin==true){
            return $query
            ->where('statut','=', 'enattente')
            ->orWhere('statut','=', 'reenattenteParAdmin');
          }else{
            return $query
            ->where('user_id','=',$UserID)
            ->Where(function ($query){
                 return $query 
                 ->where('statut','=', 'encours')
                 ->orWhere('statut','=', 'reenvoye')
                 ->orWhere('statut','=', 'reencours')
                 ->orWhere('statut','=', 'enattente')
                 ->orWhere('statut','=', 'reenattenteParAdmin');
            });
          }
  })
  ->orderBy('created_at', 'desc')->get();

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
return response()->json($returnArr);
}



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






public function filter(Request $request){

  $page = $request->input('page');
  $itemsPerPage = $request->input('itemsPerPage');
  //$nodes = $request->input('nodes');

  $skipped = ($page - 1) * $itemsPerPage;
  $endItem = $skipped + $itemsPerPage;

  $filter = $request->input('filter');
  $datemin = $filter['datemin'];
  $datemax = $filter['datemax'];
  $datemin = app('App\Http\Controllers\Divers\generale')->ReglageDateMinDateMax($datemin, 'datemin');
  $datemax = app('App\Http\Controllers\Divers\generale')->ReglageDateMinDateMax($datemax, 'datemax');
  $searchFilterText = $filter['searchFilterText'];

$statusencours=$filter['statusencours']; if($statusencours){$statusencours='true';}
$statusreporte=$filter['statusreporte']; if($statusreporte){$statusreporte='true';}
$statusconfirme=$filter['statusconfirme']; if($statusconfirme){$statusconfirme='true';}
$statusrejete=$filter['statusrejete']; if($statusrejete){$statusrejete='true';}
$all=$filter['all']; if($all){$all='true';}


$delaimin=$filter['delaimin'];
$periodedelaimin=$filter['periodedelaimin'];
$delaimin=$this->calculerDelai($delaimin,$periodedelaimin);

$delaimax=$filter['delaimax'];
$periodedelaimax=$filter['periodedelaimax'];
$delaimax=$this->calculerDelai($delaimax,$periodedelaimax);

/*
$alll=$datemin.'|'.$datemax.'|'.$searchFilterText.'|'.$statusencours.'|'.$statusreporte.'|'.$statusconfirme.'|'.$statusrejete.'|'.$all.'|'.$delaimin.'|'.$periodedelaimin.'|'.$delaimax.'|'.$periodedelaimax;
return response()->json($alll);
*/
  
  $UserID = JWTAuth::user()->id;
  $me = app('App\Http\Controllers\Divers\generale')->getUserPosts($UserID);
  $me = $me->original;
  $me = $me[0];
  $posts = $me->posts;
  

  /*
  $isAdmin = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, ['Admin']);
  $isMethode = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, ['Methode']);
  $OthersAuthorized = ['ChefDeEquipe', 'ChefDePoste', 'ResponsableMaintenance'];
  $isOthersAuthorized = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, $OthersAuthorized);
  */



$isAdmin=false;
$isResponsableAchat=false;
$UserID=JWTAuth::user()->id;
$user_post_ids=user_post::select('post_id')->where('user_id','=',$UserID)->get();
foreach($user_post_ids as $user_post_id){
    $user_post_id_v= $user_post_id['post_id'];
    $post=post::select('post')->where('PostID','=',$user_post_id_v)->first();
    $post=$post->post;
    if($post=='ResponsableAchat'){$isResponsableAchat=true;}
    if($post=='Admin'){$isAdmin=true;}
}

$returnArr=Array();

  //$uservar = User::where('id', $userId)->first();
  //$das = da::limit(100)->where('remarques','like',$searchFilterText)->orderBy('created_at', 'desc')->get();
  //$das = da::where('statut')->get(); reenvoye

  $das = da::

  Where(function ($query) use($isAdmin,$isResponsableAchat,$UserID,$statusencours,$statusreporte,$statusconfirme,$statusrejete,$all) {
    if( ( $isResponsableAchat==true ) ){
      return $query
  //--------->

  ->Where(function ($query) use($statusencours) {
    if( ( $statusencours=='true' ) ){
          return $query
          ->where('statut','=', 'encours')
          ->orWhere('statut','=', 'reenvoye')
          ->orWhere('statut','=', 'reencours')
          ->orWhere('statut','=', 'enattente')
          ->orWhere('statut','=', 'reenattenteParAdmin');
    }
  })
  ->orWhere(function ($query) use($statusreporte) {
    if( ( $statusreporte=='true' ) ){
          return $query
          ->where('statut','=', 'reporte')
          ->orWhere('statut','=', 'reporteParAdmin');
    }
  })
  ->orWhere(function ($query) use($statusconfirme) {
    if( ( $statusconfirme=='true' ) ){
          return $query
          ->where('statut','=', 'confirme')
          ->orWhere('statut','=', 'confirmeCommande')
          ->orWhere('statut','=', 'confirmeFerme')
          ->orWhere('statut','=', 'confirmeParAdmin')
          ->orWhere('statut','=', 'confirmeParAdminCommande')
          ->orWhere('statut','=', 'confirmeParAdminFerme');
    } 
  })
  ->orWhere(function ($query) use($statusrejete) {
    if( ( $statusrejete=='true' ) ){
          return $query
          ->where('statut','=', 'rejete')
          ->orWhere('statut','=', 'rejeteParAdmin');
    }
  })
  ->orWhere(function ($query) use($statusrejete,$statusconfirme,$statusreporte,$statusencours) {
    if( ( $statusrejete!='true' && $statusconfirme!='true' && $statusreporte!='true' && $statusencours!='true' ) ){
          return $query
          ->where('statut','=', 'rien');
    }
  });
//###########

    }else if ( $isAdmin==true ){
      return $query 

   //----------->
      ->Where(function ($query) use($statusencours,$all) {
        if( ( $statusencours=='true' && $all!='true') ){
              return $query
              ->where('statut','=', 'enattente')
              ->orWhere('statut','=', 'reenattenteParAdmin');
        }
        if( ( $statusencours=='true' && $all=='true' ) ){
          return $query
          ->where('statut','=', 'enattente')
          ->orWhere('statut','=', 'reenattenteParAdmin')
          ->orWhere('statut','=', 'encours')
          ->orWhere('statut','=', 'reenvoye')
          ->orWhere('statut','=', 'reencours');
       }

      })
      ->orWhere(function ($query) use($statusreporte,$all) {
        if( ( $statusreporte=='true' && $all!='true' ) ){
              return $query
              ->where('statut','=', 'reporteParAdmin');
        }
        if( ( $statusreporte=='true' && $all=='true' ) ){
          return $query
          ->where('statut','=', 'reporteParAdmin')
          ->orWhere('statut','=', 'reporte');
    }
      })
      ->orWhere(function ($query) use($statusconfirme,$all) {
        if( ( $statusconfirme=='true'  && $all!='true' ) ){
              return $query
              ->where('statut','=', 'confirmeParAdmin')
              ->orWhere('statut','=', 'confirmeParAdminCommande')
              ->orWhere('statut','=', 'confirmeParAdminFerme');
        } 
        if( ( $statusconfirme=='true'  && $all=='true'  ) ){
          return $query
          ->where('statut','=', 'confirmeParAdmin')
          ->orWhere('statut','=', 'confirmeParAdminCommande')
          ->orWhere('statut','=', 'confirmeParAdminFerme')
          ->orWhere('statut','=', 'confirme')
          ->orWhere('statut','=', 'confirmeCommande')
          ->orWhere('statut','=', 'confirmeFerme');

        } 
      })
      ->orWhere(function ($query) use($statusrejete,$all) {
        if( ( $statusrejete=='true' && $all!='true' ) ){
              return $query
              ->where('statut','=', 'rejeteParAdmin');
        }
        if( ( $statusrejete=='true' && $all=='true') ){
          return $query
          ->where('statut','=', 'rejeteParAdmin')
          ->orWhere('statut','=', 'rejete');
       }

      })
      ->orWhere(function ($query) use($statusrejete,$statusconfirme,$statusreporte,$statusencours) {
        if( ( $statusrejete!='true' && $statusconfirme!='true' && $statusreporte!='true' && $statusencours!='true' ) ){
              return $query
              ->where('statut','=', 'rien');
        }
      });

    //###########


    }else{
      return $query 
      //----------->
->where('user_id','=',$UserID)
->Where(function ($query) use($isAdmin,$isResponsableAchat,$UserID,$statusencours,$statusreporte,$statusconfirme,$statusrejete){ //374 start
      return $query   
      ->Where(function ($query) use($statusencours) {
        if( ( $statusencours=='true' ) ){
              return $query
              ->where('statut','=', 'encours')
              ->orWhere('statut','=', 'reenvoye')
              ->orWhere('statut','=', 'reencours')
              ->orWhere('statut','=', 'enattente')
              ->orWhere('statut','=', 'reenattenteParAdmin');
        }
      })
      ->orWhere(function ($query) use($statusreporte) {
        if( ( $statusreporte=='true' ) ){
              return $query
              ->where('statut','=', 'reporte')
              ->orWhere('statut','=', 'reporteParAdmin');
        }
      })
      ->orWhere(function ($query) use($statusconfirme) {
        if( ( $statusconfirme=='true' ) ){
              return $query
              ->where('statut','=', 'confirme')
              ->orWhere('statut','=', 'confirmeCommande')
              ->orWhere('statut','=', 'confirmeFerme')
              ->orWhere('statut','=', 'confirmeParAdmin')
              ->orWhere('statut','=', 'confirmeParAdminCommande')
              ->orWhere('statut','=', 'confirmeParAdminFerme');
        } 
      })
      ->orWhere(function ($query) use($statusrejete) {
        if( ( $statusrejete=='true' ) ){
              return $query
              ->where('statut','=', 'rejete')
              ->orWhere('statut','=', 'rejeteParAdmin');
        }
      })
      ->orWhere(function ($query) use($statusrejete,$statusconfirme,$statusreporte,$statusencours) {
        if( ( $statusrejete!='true' && $statusconfirme!='true' && $statusreporte!='true' && $statusencours!='true' ) ){
              return $query
              ->where('statut','=', 'rien');
        }
      });

 });//374 start

   //###########
  }
})
->orderBy('created_at', 'desc')->get();



 // $das = DB::table('das')
/*
  ->Where(function ($query) use($searchFilterText) {
    if(! ( $searchFilterText=='' ) ){
          return $query
          ->where('remarques','like', '%'.$searchFilterText.'%');
         // ->Where('users.email', 'LIKE', '%'.$searchFilterText.'%');
          //->orWhere('name', 'LIKE', '%'.$searchFilterText.'%');
        }
  })

  ->Where(function ($query) use($datemin) {
    if(! ( $datemin=='' ) ){
          return $query->where('created_at','>=',$datemin); }
  })
  ->Where(function ($query) use($datemax) {
    if(! ( $datemax=='' ) ){
          return $query->where('created_at','<=',$datemax); }
  })
  ->orderBy('created_at', 'desc')->get();
*/
// ma nansech lazim requete ma te3adda mel front kén ma nthabbit datemin is number mech 7rouf .. 

//is_int($delaimin)

/*
if(  $delaimin !='' ){
  $das=$das->filter(function ($value, $key) use($delaimin) {
  $delaiminimale=$value['delai'];
  $date_de_creation = Carbon::parse($value['created_at']);
  $today = Carbon::now();
  $diff=$date_de_creation->diffInDays($today,false);
  $reste_delai= ( $delaiminimale - $diff ) -1 ; 
  return $reste_delai <= $delaimin ;
});
}
*/

if(  $searchFilterText !='' || $delaimin !='' || $delaimax !='' || $datemin!='' || $datemax!=''){
$das=$das->filter(function ($value, $key) use($searchFilterText,$delaimin,$delaimax,$datemin,$datemax) {
  return $this->toFilter($value['id'],$searchFilterText,$delaimin,$delaimax,$datemin,$datemax);
});
}



/*
$DateEvenement = date('jan 30 00:00:00 2018');
$DateNow = date("Y/m/d");
$TempsRestant = $DateNow->diff($DateEvenement);
$TempsRestant->d;
return response()->json($TempsRestant->d);
*/



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

  

$countQuery = count($returnArr); //->count();
$array_after_skipped=array_slice($returnArr,$skipped);
$array_take_items=array_slice($array_after_skipped,0,$itemsPerPage);
$das=$array_take_items;


  //$countQuery = $dis->count();
  //$countQuery = 200;
  //$dis = $dis->skip($skipped)->take($itemsPerPage)->get();
  return response()->json(['das' => $das, 'me' => $me, 'total' => $countQuery]);

  //return response()->json($returnArr);


//return response()->json($request);


}



public function calculerDelai($delai,$periode){
  //if( !is_int($delai) ){ return 'bannour'; }
  $unite=1;
  if($periode=="jour"){$unite=1;}  if($periode=="semaine"){$unite=7;} if($periode=="mois"){$unite=30;}if($periode=="annee"){$unite=365;}
  $delaiFinale= (int)( (int)$delai* $unite);
  return (int)$delaiFinale;
}


public function toFilter($DaID,$searchFilterText,$delaimin,$delaimax,$datemin,$datemax){
  $retour_search=true;
  $retour_delaimin=true;
  $retour_delaimax=true;
  $retour_datemin=true;
  $retour_datemax=true;
   
  if($searchFilterText!=''){
    $retour_search=false;

      $user_id_remarques=da::select('user_id','remarques')->where('id','=',$DaID)->first();
      $user_id=$user_id_remarques->user_id;
      $remarques=$user_id_remarques->remarques;
      if( stripos($remarques, $searchFilterText) !== FALSE){ $retour_search =true ;} 
      $demandeur=User::select('name')->where('id','=',$user_id)->first();
      $demandeur=$demandeur->name;
      if( stripos($demandeur, $searchFilterText) !== FALSE){ $retour_search =true ;} 
      
   $arr_da_articles=daarticle::select('article_id')->where('da_id','=',$DaID)->get();
   $arr_articles=Array();
  
   foreach($arr_da_articles as $article_id){
     $ArticleID=$article_id['article_id'];
    // $arr_articles=article::select('fournisseur_id','des','code_article','code_a_barre','famille','sous_famille')
     $arr_articles=article::select('fournisseur_id','des','code_article','code_a_barre')
     ->where('ArticleID','=',$ArticleID)->get();
    
     foreach($arr_articles as $article){
      if( stripos($article['des'], $searchFilterText) !== FALSE){ $retour_search =true ;  } 
      if( stripos($article['code_article'], $searchFilterText) !== FALSE){ $retour_search =true ;  } 
      if( stripos($article['code_a_barre'], $searchFilterText) !== FALSE){ $retour_search =true ;  } 
      
      
      $fournisseur=fournisseur::select('nom')->where('id','=',$article['fournisseur_id'])->first();
      $fournisseur=$fournisseur->nom;
      if( stripos($fournisseur, $searchFilterText) !== FALSE){ $retour_search =true ; } 
      
      }

    }  

    
     };


     if(  $delaimin !='' ){ 
      $retour_delaimin=false;
     
      $da_delai_created=da::select('delai','created_at')->where('id','=',$DaID)->first();
      $delaiminimale=$da_delai_created->delai;
      $created_at=$da_delai_created->created_at;
      $date_de_creation = Carbon::parse($created_at);
      $date_de_creation->hour(0)->minute(0)->second(0);
      $today = Carbon::now();
      $today->hour(0)->minute(0)->second(0);
      $diff=$date_de_creation->diffInDays($today,false);
      $reste_delai= ( $delaiminimale - $diff )  ; 
      $retour_delaimin = $reste_delai <= $delaimin ;
 
    }


    if(  $delaimax !='' ){
      $retour_delaimax=false;
     
      $da_delai_created=da::select('delai','created_at')->where('id','=',$DaID)->first();
      $delaiminimale=$da_delai_created->delai;
      $created_at=$da_delai_created->created_at;
      $date_de_creation = Carbon::parse($created_at);
      $date_de_creation->hour(0)->minute(0)->second(0);
      $today = Carbon::now();
      $today->hour(0)->minute(0)->second(0);

      $diff=$date_de_creation->diffInDays($today,false);
      $reste_delai= ( $delaiminimale - $diff )  ; 
      
      $retour_delaimax = $reste_delai >= $delaimax ;
 
    }

    //$datemin=$request->input('datemin');
    
    if($datemin!=''){
      $retour_datemin=false;

      $datemin= Carbon::parse($datemin);
      $datemin->hour(0)->minute(0)->second(0);
      $created=da::select('created_at')->where('id','=',$DaID)->first();
      $created_at=$created->created_at;
      $date_de_creation = Carbon::parse($created_at);
      $date_de_creation->hour(0)->minute(0)->second(0);
      $diff= $date_de_creation->diffInDays($datemin,false) ;
      $retour_datemin=  $diff < 0 ; 

    }
    //$datemax=$request->input('datemax'); 
    if($datemax!=''){
      $retour_datemax=false;
      $datemax= Carbon::parse($datemax);
      $datemax->hour(0)->minute(0)->second(0);
      $created=da::select('created_at')->where('id','=',$DaID)->first();
      $created_at=$created->created_at;
      $date_de_creation = Carbon::parse($created_at);
      $date_de_creation->hour(0)->minute(0)->second(0);
      $diff= $date_de_creation->diffInDays($datemax,false)+1 ;
      $retour_datemax=  $diff >=0 ; 
    }



$retour_finale = $retour_search && $retour_delaimin && $retour_delaimax  && $retour_datemin && $retour_datemax ;


  return $retour_finale;

  }



}

