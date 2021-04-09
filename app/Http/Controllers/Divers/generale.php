<?php

namespace App\Http\Controllers\Divers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\User;
use Carbon\Carbon;
use JWTFactory;
use JWTAuth;
use phpDocumentor\Reflection\Types\Boolean;

use App\Model\Divers\intervenant;
use App\Model\Magasin\art_outil;
use App\Model\Equipement\equi_tache;
use App\Model\Magasin\article;
use App\Model\Divers\articles_perdu;
use App\Model\Preventif\prev_bsm;
use App\Model\Preventif\prev_otp;


use App\Model\Preventif\prev_reservation;
use App\Model\Preventif\prev_reservation_det;

use App\Model\Preventif\prev_intervention;

use App\Model\Divers\error;


class generale extends Controller
{
    public function getUsersPosts(Request $request){
        
        //all
        $arrPosts=$request->input('posts');

        $users= user::
        join('user_posts', function ($join)  {
            $join->on('user_posts.user_id', '=', 'users.id');
        })
        ->join('posts', function ($join) use($arrPosts) {
            $join->on('posts.PostID', '=', 'user_posts.post_id')
            ->Where(function ($query) use($arrPosts) {
                if( count($arrPosts)>0 ){
                     return $query->whereIn('posts.post', $arrPosts); 
                    }else{
                    return $query;//->where('cmds.statut','=', 'rien');
                    }
              });
         })
        ->select('users.id','users.name','posts.post')
        ->get();
       // ->lists('users.id','posts.post') // ->with('users.id') //   //->pluck('users.id','posts.post'); // //$users = array_column($users->toArray(), 'id');
    
       $arr=Array();
       foreach($users as $user) 
       {
          if(!isset($arr[$user['id']])){
        
            $ligne=new \stdClass();
            $ligne->id=$user['id'];
            $ligne->name=$user['name'];
        
            $posts=Array();
            foreach($users as $user2){
             if($user2['id']==$user['id']){array_push($posts,$user2['post']);}
            }
            $ligne->posts=$posts;

            $arr[$user['id']]=$ligne;
          }
       }

       $arr2=Array();
       foreach($arr as $a){ array_push($arr2,$a); }


       // me 
       $UserID=JWTAuth::user()->id;
       $me=$this->getUserPosts($UserID);
       $me = $me->original;
       
       return response()->json(['me'=>$me,'users'=>$arr2]);

    }


    public function getUserPosts($id){

        $users= user::where('id','=',$id)
        ->join('user_posts', function ($join)  {
            $join->on('user_posts.user_id', '=', 'users.id');
        })
        ->join('posts', function ($join) {
            $join->on('posts.PostID', '=', 'user_posts.post_id');
         })
        ->select('users.id','users.name','posts.post','posts.fonction')
        ->get();                                       // --
       // ->lists('users.id','posts.post') // ->with('users.id') //   //->pluck('users.id','posts.post'); // //$users = array_column($users->toArray(), 'id');

    
       $arr=Array();
       foreach($users as $user) 
       {
          if(!isset($arr[$user['id']])){
        
            $ligne=new \stdClass();
            $ligne->id=$user['id'];
            $ligne->name=$user['name'];
        
            $posts=Array(); 
            $fonctions=Array();// --
            foreach($users as $user2){
             if($user2['id']==$user['id']){
               array_push($posts,$user2['post']);
               array_push($fonctions,$user2['fonction']); // --
              }
            }
            $ligne->posts=$posts;
            $ligne->fonctions=$fonctions;// --

            $arr[$user['id']]=$ligne;
          }
       }

       $arr2=Array();
       foreach($arr as $a){ array_push($arr2,$a); }

       return response()->json($arr2);
    }




    public function PostsHavePosts($userPosts,$arrPosts){
        for ($i = 0;  $i<count($userPosts); $i++ ) {
          $post=$userPosts[$i];
          if (in_array($post, $arrPosts)){return true;}
        }
        return false;
    }


    public function getIntervenants(){
      $intervenants=intervenant::where('exist','=',1)->get();
      return response()->json($intervenants);
    }


  public function ReglageDateMinDateMax($date,$dateMinOrDateMax){
    
    $dateStart = new Carbon('first day of last month'); 
    $dateStart->hour(0)->minute(0)->second(0);

    if($dateMinOrDateMax=='datemin'){
    if($date!=''){
        $nvdate= Carbon::parse($date);
        $nvdate->hour(0)->minute(0)->second(0);
     }else{$nvdate=$dateStart;}
    }

    if($dateMinOrDateMax=='datemax'){
     if($date!=''){ 
       $nvdate= Carbon::parse($date);
       $nvdate->hour(0)->minute(0)->second(0);
     }else{$nvdate=Carbon::parse('2120-01-01 00:00:00');}
    }
    
    if($dateMinOrDateMax=='minimum'){
    $nvdate=Carbon::parse('2020-01-01 00:00:00');
    }
    
    return $nvdate;

  }



  
  public function getOutilsBySearchWord(Request $request){
    $search = $request->input('search');
    return art_outil::where('des', 'Like', '%' . $search . '%')
    ->where('art_outils.exist','=',1)
    ->get();
  }

  public function getIntervenantsBySearchWord(Request $request){
    $search = $request->input('search');
    return intervenant::where('name', 'Like', '%' . $search . '%')
    ->where('intervenants.exist','=',1)
    ->get();
  }

  public function getTachesBySearchWord(Request $request){
    $search = $request->input('search');
    return equi_tache::where('tache', 'Like', '%' . $search . '%')
    ->where('equi_taches.exist','=',1)
    ->get();
  }

  public function getTachesBySearchWordByEquipementID(Request $request){
    $search = $request->input('search');
    $EquipementID = $request->input('EquipementID');
    
    /*
    return equi_tache::where('tache', 'Like', '%' . $search . '%')
    ->where('equi_taches.exist','=',1)
    ->get();
   */

    return equi_tache::where('tache', 'Like', '%' . $search . '%')
    ->where('equi_taches.exist','=',1)
    ->join('equi_taches_equipements', function ($join) use($EquipementID) {
      $join->on('equi_taches_equipements.tache_id', '=', 'equi_taches.TacheID')
      ->where('equi_taches_equipements.equipement_id','=',$EquipementID);
    })
    ->distinct('equi_taches.TacheID')
    ->get('equi_taches.*');
    

  }

  public function getTachesBySearchWordByInterventionID(Request $request){
    $search = $request->input('search');
    $InterventionID = $request->input('InterventionID');
    
   // error::insert(['error'=>$OtpID]);

  /*
    $prev_otp=prev_otp::where('OtpID','=',$OtpID)
    ->join('prev_interventions', function ($join) {
      $join->on('prev_interventions.InterventionID', '=', 'prev_otps.intervention_id')
      ->where('prev_interventions.exist','=',1);
    })
    ->first();
*/
    $prev_intervention=prev_intervention::where('InterventionID','=',$InterventionID)->first();


    //error::insert(['error'=>$prev_otp]);


    $EquipementID=$prev_intervention->equipement_id;

    
    /*
    return equi_tache::where('tache', 'Like', '%' . $search . '%')
    ->where('equi_taches.exist','=',1)
    ->get();
   */

    return equi_tache::where('tache', 'Like', '%' . $search . '%')
    ->where('equi_taches.exist','=',1)
    ->join('equi_taches_equipements', function ($join) use($EquipementID) {
      $join->on('equi_taches_equipements.tache_id', '=', 'equi_taches.TacheID')
      ->where('equi_taches_equipements.equipement_id','=',$EquipementID);
    })
    ->distinct('equi_taches.TacheID')
    ->get('equi_taches.*');
    

  }


  public function UpdateStockByArticleID($ArticleID,$Qte){
    if($Qte!=0){
    $article=article::where('ArticleID','=',$ArticleID)->increment('stock',$Qte);
    return $article;
    if($article){
      return true;
    }else{
     return false;
    }
   }else{
     return true;
   }

  }

  public function UpdatePerduByArticleID($ArticleID,$Qte){
    if($Qte!=0){
    $article=articles_perdu::where('article_id','=',$ArticleID)->increment('qtep',$Qte);
    return $article;
    if($article){
      return true;
    }else{
     return false;
    }
   }else{
     return true;
   }

  }

  public function ArticlePerdu($ArticleID,$Qte){
    $articles_perdus=articles_perdu::where('article_id','=',$ArticleID)->get();
    if(count($articles_perdus)==0){
     // create
     // if Qte méch logique !! 
     $articles_perdu=articles_perdu::create([
       'article_id'=>$ArticleID,
       'qtep'=>$Qte
     ]);
     return $articles_perdu;
    }else{
     // modification 
      return $this->UpdatePerduByArticleID($ArticleID,$Qte);
    }
  }


  
  
  public function ArticleReserved($ArticleID,$Qte){
    //if($Qte>){
    $article=article::where('ArticleID','=',$ArticleID)->increment('reserved',$Qte);
    //return $article;
    if($article){
      return true;
    }else{
     return false;
    }

   /*
   }else{
     return true;
   }
   */

  }
  

  public function ModificationDate(Request $request){
    $success=true;
    $id=$request->id;
    $whattoedit=$request->whattoedit;
    $date=$request->date;

    if($whattoedit=='otp_date_execution'){
     $prev_otp=prev_otp::where('OtpID','=',$id)->update(['date_execution'=>$date]);
     app('App\Http\Controllers\Preventif\preventif')->MethodeGenerale($id,$date,'modification');
     //if(!$prev_otp){$success=false;}
    }

    return response()->json(['success'=>$success,'date'=>$date]);

  }


  public function DeleteReservedArticle($ArticleID,$BsmID){
  
    $prev_bsm=prev_bsm::where('BsmID','=',$BsmID)->first();
    $OtpID=$prev_bsm->otp_id;
    $prev_reservation=prev_reservation::where('otp_id','=',$OtpID)->first();
    if($prev_reservation){
    $ReservationID=$prev_reservation->ReservationID;
    $prev_reservation_det=prev_reservation_det::where('reservation_id','=',$ReservationID)->where('article_id','=',$ArticleID)->first();
    $qte=$prev_reservation_det->qte;
    prev_reservation_det::where('reservation_id','=',$ReservationID)->where('article_id','=',$ArticleID)->delete();
    $this->ArticleReserved($ArticleID,-$qte);
    
    $prev_reservation_dets=prev_reservation_det::where('reservation_id','=',$ReservationID)->get();
    if(!(count($prev_reservation_dets)>0)){
      prev_reservation::where('ReservationID','=',$ReservationID)->delete();
    }
  }

    
  }

  

  





}
