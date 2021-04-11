<?php

namespace App\Http\Controllers\Achat\cmd;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;
use App\Model\Achat\fournisseur;
use App\Model\Achat\da;
use App\Model\Achat\daarticle;
use App\Model\Achat\cmd;
use App\Model\Achat\cmd_daarticle;
use App\Model\Achat\cmd_reception;
use App\Model\Achat\cmd_reception_det;
use App\Model\Achat\cmd_reception_modif;
use App\Model\Achat\cmd_reception_modif_det;
use App\Model\Achat\cmd_bon_det;
use App\Model\Achat\cmd_bon;
use App\Model\Achat\cmd_corb_bon;
use App\Model\Achat\cmd_corb_bon_det;
use App\Model\Achat\cmd_retour;
use App\Model\Achat\cmd_retour_det;
use App\Model\Achat\cmd_corb_retour;
use App\Model\Achat\cmd_corb_retour_det;

use App\Model\Achat\cmd_facture;
use App\Model\Achat\cmd_facture_det;

use App\Model\Magasin\article;
use App\Model\Magasin\art_pp;

use Carbon\Carbon;
use JWTFactory;
use JWTAuth;

use App\Model\Divers\error;


class cmdController extends Controller
{
    
   public function getfournisseurcmde(){
    $res = DB::table('das')->where('statut', '=', 'confirme')->orWhere('statut','=','confirmeParAdmin')
    ->orWhere('statut','=','confirmeParAdminCommande')->orWhere('statut','=','confirmeCommande')
    ->join('daarticles', function ($join) {
        $join->on('das.id', '=', 'daarticles.da_id');
    })
    ->join('articles', function ($join) {
        $join->on('daarticles.article_id', '=', 'articles.ArticleID');
    })
    ->join('fournisseurs', function ($join) {
        $join->on('fournisseurs.id', '=', 'articles.fournisseur_id');
    })
    ->select('fournisseurs.id','fournisseurs.nom')
    ->get();
    //$res=(array)$res; //$res = array_unique($res,SORT_REGULAR);
    $uniquesf=array();foreach($res as $f) { $key = $f->id; $uniquesf[$key] = $f;}
    $fournisseurs=array();foreach($uniquesf as $f) { $req=new Request(); $f->count=$this->getarticlecmde($req,$f->id); array_push($fournisseurs,$f) ;}
    return response()->json($fournisseurs);
   }


   public function getarticlecmde(Request $request,$id){
   $count=$request->input('count');
    //$CmdDaArticleIds = cmddaarticle::pluck('daarticle_id')->all();
    $res = DB::table('das')->where('statut', '=', 'confirme')->orWhere('statut','=','confirmeParAdmin')
    ->orWhere('statut','=','confirmeParAdminCommande')->orWhere('statut','=','confirmeCommande')
    ->join('daarticles', function ($join) /*use($crashedCarIds)*/ {
        $join->on('das.id', '=', 'daarticles.da_id')
        //->whereNotIn('daarticles.id',$CmdDaArticleIds);
        ->whereNotExists( function ($query) {
            $query->select(DB::raw(1))
            ->from('cmd_daarticles')
            ->join('cmds', function ($join) {
                $join->on('cmds.CommandeID', '=', 'cmd_daarticles.commande_id')
                ->where('cmds.exist','=',1);
            })
            ->whereRaw('cmd_daarticles.daarticle_id = daarticles.id');
        })
        /*
        ->where(function ($query) {
            $query->select(DB::raw(1))
            ->from('cmds')
            ->whereRaw('cmddaarticles.daarticle_id = daarticles.id');
        })
        */
        ;

    })
    
    ->join('articles', function ($join) {
        $join->on('daarticles.article_id', '=', 'articles.ArticleID');
    })
    
    ->join('fournisseurs', function ($join) use($id) {
        $join->on('fournisseurs.id', '=', 'articles.fournisseur_id')
        ->where('fournisseurs.id','=',$id);
    })
    ->join('users', function ($join) {
        $join->on('das.user_id', '=', 'users.id');
    })
    ->select('users.name','articles.ArticleID','articles.fournisseur_id','articles.des','daarticles.id','daarticles.qte','daarticles.motif','das.ref','das.delai','das.remarques','das.created_at');
    //->orderBy('created_at', 'desc')
    if($count=='0') { $res=$res->get(); return response()->json($res);} 
    else { $res=$res->get()->count(); return $res;  }
    //->get();
    
   }



   public function addcmd(Request $request){
    $success=true;
    $statut='ouvert';
    $UserID=JWTAuth::user()->id;
    $FournisseurID=$request->fournisseur_id;
    $CmdDaArticles=$request->cmddaarticles;

    //--- Logique Test
    $isValid=true;
    $TTCmde=0;
    foreach($CmdDaArticles as $CmdDaArticle){
        $QteDemande=daarticle::where('id','=',$CmdDaArticle['DaArticleID'])->select('qte')->first();
        $QteDemande=$QteDemande->qte; $QteDemande=($QteDemande*1)+0;
        $QteCommande=($CmdDaArticle['QteCmde']*1)+0;
        $TTCmde=$TTCmde+$QteCommande;
        if( $this->MathRound($QteCommande) > $this->MathRound($QteDemande) || ($QteCommande < 0) ){$isValid=false;}
        }
        if( $TTCmde<0 ){$isValid=false;}
        if($TTCmde==0){$statut='vide';}
        if(!$isValid){return response()->json($isValid);}
    //### Logique Test 

    $retour = cmd::create([
        'user_id'=>$UserID,
        'fournisseur_id'=>$FournisseurID,
        'statut'=>$statut
    ]);
    $CommandeID=$retour->CommandeID;
    if($retour){
       foreach($CmdDaArticles as $CmdDaArticle){


        $retour=cmd_daarticle::create([
            'commande_id'=>$CommandeID,
            'daarticle_id'=>$CmdDaArticle['DaArticleID'],
            'QteCmde'=>$CmdDaArticle['QteCmde']
        ]);
        if($retour){$this->statutDaToCommande($CmdDaArticle['DaArticleID']);}
        if(!$retour){$success=false;}

        }
    }else{
     $success=false;
    };
    
    return response()->json($success);

   }


   public function getlistecmd(Request $request){
    $start = new Carbon('first day of last month'); $start->hour(0)->minute(0)->second(0);
    $cmds=cmd::where('exist','=',1)->whereDate('cmds.created_at','>',$start)
    ->where('cmds.statut','=', 'ouvert')
    ->join('fournisseurs', function ($join) {
        $join->on('fournisseurs.id', '=', 'cmds.fournisseur_id');
    })
    ->join('users', function ($join) {
        $join->on('users.id', '=', 'cmds.user_id');
    })
    ->select('cmds.*','fournisseurs.nom','users.name')
    ->orderBy('created_at', 'desc')
    ->get();
    return response()->json($cmds);
   }


   public function getcmd(Request $request,$id){
      $cmd=cmd::where('CommandeID','=',$id)
      ->join('fournisseurs', function ($join) {
          $join->on('fournisseurs.id', '=', 'cmds.fournisseur_id');
      })
      ->join('users', function ($join) {
          $join->on('users.id', '=', 'cmds.user_id');
      })
      ->select('cmds.*','fournisseurs.nom','users.name')
      ->get();
      
      $cmddet=cmd_daarticle::where('commande_id','=',$id)
      ->join('daarticles', function ($join) {
        $join->on('daarticles.id', '=', 'cmd_daarticles.daarticle_id');
      })
      ->join('articles', function ($join) {
        $join->on('articles.ArticleID', '=', 'daarticles.article_id');
      })
      ->join('unites', function ($join) {
        $join->on('unites.UniteID', '=', 'articles.unite_id');
      })
      ->join('das', function ($join) {
        $join->on('das.id', '=', 'daarticles.da_id');
      })
      ->join('users', function ($join) {
        $join->on('users.id', '=', 'das.user_id');
      })
      ->select('cmd_daarticles.*','daarticles.article_id','articles.des','unites.unite','users.name','das.ref','das.id','daarticles.qte')
      ->get();

      $res=new \stdClass();
      $res->cmd=$cmd;
      $res->cmddet=$cmddet;

      return response()->json($res);
   }

   public function cmdcorbeille(Request $request){
    $cmds=cmd::where('exist','=',0)
    ->join('fournisseurs', function ($join) {
        $join->on('fournisseurs.id', '=', 'cmds.fournisseur_id');
    })
    ->join('users', function ($join) {
        $join->on('users.id', '=', 'cmds.user_id');
    })
    ->select('cmds.*','fournisseurs.nom','users.name')
    ->get();
    return response()->json($cmds);
   }

   public function deletecmd(Request $request,$id){
    $success=false;
    $nbReceptions=0;
    $req=new Request(); $nbReceptions=count($this->cmdreceptions($req,$id)); 
    if($nbReceptions==0){
    $update =  cmd::where('CommandeID','=', $id)->update(['exist'=>0]);
    $this->ChangementsNecessaireApresSupressionCmd($id);
    if($update){$success=true;}
    }
    return response()->json($success);
   }

   public function restaurercmd(Request $request,$id){
    // Logique Test
    $success=false;
    $req=new Request(); 
    $count=$this->droitrestaurationcmd($req,$id,'restaurercmd'); 
    //#############
    if($count==0){
      $update =  cmd::where('CommandeID','=', $id)->update(['exist'=>1]);
      if($update){$success=true;$this->ChangementsNecessaireApresRestaurerCmd($id);}
    }
    return response()->json($success);
   }


   public function nvreception(Request $request){

    $success=true;
    $msg="Reception est succées .";

    $UserID=JWTAuth::user()->id;
    $CommandeID=$request->CommandeID;
    $ReceptionDets=$request->reception_det;
    $rapport=$request->rapport;

    if(!$this->ExistCmd($CommandeID)){$msg="Error ." ; return response()->json(['success'=>false,'msg'=>$msg]);}

    $isValid=true;
       foreach($ReceptionDets as $ReceptionDet){
           $Reste= cmd_daarticle::where('daarticle_id','=',$ReceptionDet['daarticle_id'])
           ->join('cmds', function ($join) {
            $join->on('cmds.CommandeID', '=', 'cmd_daarticles.commande_id')
            ->where('cmds.exist','=',1);
           })
           ->select('QteCmde','TTQteRecu')->first();
           $Reste=$Reste->QteCmde - $Reste->TTQteRecu ;
          
           $Reste=$this->MathRound(($Reste*1)+0);
           $ReceptionDet['QteRecu']=$this->MathRound(($ReceptionDet['QteRecu']*1)+0);

           if( ( $ReceptionDet['QteRecu'] > $Reste) || $ReceptionDet['QteRecu']<0 ) {
            $isValid=false;
           }
       }
      // return response()->json($ReceptionDet['QteRecu'] .'>'. $Reste);
      
       if(!$isValid){
         return response()->json(['success'=>false,'msg'=>$msg]);
        }
    

    $retour = cmd_reception::create([
        'user_id'=>$UserID,
        'commande_id'=>$CommandeID,
        'rapport'=>$rapport
    ]);
    $CmdRecID=$retour->CmdRecID;

    if($retour){
       foreach($ReceptionDets as $ReceptionDet){

        //return response()->json($CmdDaArticle);

        $retour=cmd_reception_det::create([
            'cmd_rec_id'=>$CmdRecID,
            'daarticle_id'=>$ReceptionDet['daarticle_id'],
            'QteRecu'=>$ReceptionDet['QteRecu']
        ]);
        cmd_daarticle::where('daarticle_id',$ReceptionDet['daarticle_id'])
        ->join('cmds', function ($join) {
          $join->on('cmds.CommandeID', '=', 'cmd_daarticles.commande_id')
          ->where('cmds.exist','=',1);
        }) // tasli7
        ->increment('TTQteRecu',$ReceptionDet['QteRecu']); // f chak 1
        
        // Mouvement du stock // amelioration
        // Insertion dans tableau mouvements
        /*
        $Ret_ArticleID=daarticle::where('id','=',$ReceptionDet['daarticle_id'])
        ->select('daarticles.article_id')
        ->first();
        $ArticleID=$Ret_ArticleID->article_id;
        */

        $ArticleID=$this->GetArticleIDByDaarticleid($ReceptionDet['daarticle_id']);
        

        if($ArticleID){
        $article=article::where('ArticleID','=',$ArticleID)->increment('stock',$ReceptionDet['QteRecu']);
        //if(!$article){$success=false;}
        }else{
          $success=false;
          $msg="Error ." ;
        }
        //###################################

        if(!$retour){$success=false; $msg="Error ." ;}

        }
    }else{
     $success=false;
    };
    
    return response()->json(['success'=>$success,'msg'=>$msg,'CmdRecID'=>$CmdRecID]);
   }



  public function cmdreceptions(Request $request,$id){
     return cmd_reception::where('commande_id','=',$id)
     ->join('users', function ($join) {
        $join->on('users.id', '=', 'cmd_receptions.user_id');
    })
    ->select('cmd_receptions.*','users.name')
     ->get();
  } 

  public function reception(Request $request,$id){

    $reception=cmd_reception::where('CmdRecID','=',$id)
    ->join('cmds', function ($join) {
      $join->on('cmds.CommandeID', '=', 'cmd_receptions.commande_id')
      ->where('cmds.exist','=',1);
    })
    ->join('fournisseurs', function ($join) {
      $join->on('cmds.fournisseur_id', '=', 'fournisseurs.id');
    })
    ->join('users', function ($join) {
        $join->on('users.id', '=', 'cmd_receptions.user_id');
    })
    ->select('cmd_receptions.*','users.name','fournisseurs.nom','fournisseurs.TVA')
    ->first();

    $reception_det=cmd_reception_det::where('cmd_rec_id','=',$id)
      ->join('daarticles', function ($join) {
        $join->on('daarticles.id', '=', 'cmd_reception_dets.daarticle_id');
      })
      ->join('cmd_daarticles', function ($join) {
        $join->on('daarticles.id', '=', 'cmd_daarticles.daarticle_id');
      })
      ->join('cmds', function ($join) {
        $join->on('cmds.CommandeID', '=', 'cmd_daarticles.commande_id')
        ->where('cmds.exist','=',1);
      }) // tasli7

      ->join('articles', function ($join) {
        $join->on('articles.ArticleID', '=', 'daarticles.article_id');
      })
      ->join('das', function ($join) {
        $join->on('das.id', '=', 'daarticles.da_id');
      })
      ->join('users', function ($join) {
        $join->on('users.id', '=', 'das.user_id');
      })
    ->select('cmd_reception_dets.*','articles.ArticleID','articles.des','articles.PrixHT','daarticles.qte','cmd_daarticles.QteCmde','cmd_daarticles.TTQteRecu','users.name','das.ref','das.id')
    ->get();
    
    $cmd_retours=cmd_retour::where('cmd_rec_id','=',$id)
    ->join('users', function ($join) {
      $join->on('users.id', '=', 'cmd_retours.user_id');
    })
    ->select('cmd_retours.*','users.name')
    ->get();
    
    $TTRetourDet=array();
    $TTRetourDet=$this->TTRetourDetByCmd_retours($cmd_retours);
    
    //GetAnPrixHTByArticleID
    // last price
    

    $ArrArticleIDAnPrixHT=array();
    foreach($reception_det as $article){
     $ArticleID=$article['ArticleID'];
     $AnPrixHT=$this->GetAnPrixHTByArticleID($ArticleID);
     $ArticleIDAnPrixHT=new \stdClass();
     $ArticleIDAnPrixHT->ArticleID=$ArticleID;
     $ArticleIDAnPrixHT->AnPrixHT=$AnPrixHT;
     array_push($ArrArticleIDAnPrixHT,$ArticleIDAnPrixHT);
    }

    $MyCmdID=$reception->commande_id;
    $cmd=cmd::where('CommandeID','=',$MyCmdID)
      ->join('fournisseurs', function ($join) {
          $join->on('fournisseurs.id', '=', 'cmds.fournisseur_id');
      })
      ->join('users', function ($join) {
          $join->on('users.id', '=', 'cmds.user_id');
      })
      ->select('cmds.*','fournisseurs.nom','users.name')
      ->first();

    return response()->json(['reception'=>$reception,'reception_det'=>$reception_det,'Retours'=>$cmd_retours,'TTRetoursDet'=>$TTRetourDet,'ArrArticleIDAnPrixHT'=>$ArrArticleIDAnPrixHT,'cmd'=>$cmd]);
  }

  public function TTRetourDetByCmd_retours($cmd_retours){
  $TTRetourDet=array();
  foreach($cmd_retours as $cmd_retour){
    $RetourID=$cmd_retour['RetourID'];
    $cmd_retour_dets=cmd_retour_det::where('retour_id','=',$RetourID)->get();
       foreach($cmd_retour_dets as $cmd_retour_det){
        $ArticleID=$cmd_retour_det['article_id'];
        $QteRet=$cmd_retour_det['QteRet'];

        if( isset($TTRetourDet[$ArticleID]) ){
          $value=$TTRetourDet[$ArticleID];
          $value+=$QteRet;
          $TTRetourDet[$ArticleID]=$value;
        }else{
          $TTRetourDet[$ArticleID]=$QteRet;
        }
       }
    }
    return $TTRetourDet;
  }

  public function ReceptionDetsToReceptionDetSomme($ReceptionDets){
    $somme=array();
    foreach($ReceptionDets as $ReceptionDet){
     
     $daarticle_id=$ReceptionDet['daarticle_id'];
     $daarticle=daarticle::where('id','=',$daarticle_id)->first(); // nthabbit men 7keyet cmd exist = 0 
     $ArticleID=$daarticle->article_id;

     if(isset($somme[$ArticleID])){
      $somme[$ArticleID]+=$ReceptionDet['QteRecu'];
     }else{
      $somme[$ArticleID]=$ReceptionDet['QteRecu'];
     }
    }
    return $somme;
  }

  public function TTRetourDetFunc($TTRetourDet,$ArticleID){
    if(isset($TTRetourDet[$ArticleID])){
      return $TTRetourDet[$ArticleID];
    }else{
      return 0;
    }
  }

  public function TesTLogiqueEditReceptionQteRecuSupQteRet($CmdRecID,$ReceptionDets){
    $cmd_retours=cmd_retour::where('cmd_rec_id','=',$CmdRecID)->get();
    $TTRetourDet=array();
    $TTRetourDet=$this->TTRetourDetByCmd_retours($cmd_retours);
    $ReceptionDetsToReceptionDetSomme=$this->ReceptionDetsToReceptionDetSomme($ReceptionDets);
    foreach ($ReceptionDetsToReceptionDetSomme as $key => $value){
     $QteRet=$this->TTRetourDetFunc($TTRetourDet,$key);
     if($value < $QteRet){ return false;}
    }
    return true;
  }

  public function TesTLogiqueStockNotInfAZero($ArticleID,$Qte){
        $stock=$this->GetStockByArticleID($ArticleID);
        if( ($stock + $Qte) < 0 ){
          return false;
        }else{
          return true;
        }
  }

  public function editreception(Request $request,$id){
    // Na9sa 7aja Tres Important ama méch wa9tha 
    // 9bal ma tet3dda reception lazim Test Logique Tres important
    // w ki la9taye3 yatl3ou 5ajou w mstock w tsabew w yji houwa ya3mel modification 3la reception 
    // par exemple 3mal reception mte3 100 Boulouna w 5arrajhom el kol walla 90
    // ma3néha f stock 9a3dou kén 10
    // kén béch tetmodifa reception lél 0 mathalan ma tjich !! 5ater f stock béch ywalli famma -90  
    // donc lazim ndour les articles modifie kol w ntabbit m3a l'etat de stock esque logique elli béch ybadlou walla
    
    $success=true;
    $UserID=JWTAuth::user()->id;
    $ReceptionDets=$request->reception_det;
    $rapport=$request->rapport;
    $isValid=true;
    $AnNvs=Array();
    $TypeDeModif='modification';
    $TTRec=0;
    
    // Tester Si exist de Cmd = 1
    $resCmd=cmd_reception::where('CmdRecID','=',$id)->select('commande_id')->first();
    $resCommandeID=$resCmd->commande_id;
    if(!$this->ExistCmd($resCommandeID)){return response()->json(false);}
    // ##########################

    $TesTLogiqueEditReceptionQteRecuSupQteRet=$this->TesTLogiqueEditReceptionQteRecuSupQteRet($id,$ReceptionDets);
    if(!$TesTLogiqueEditReceptionQteRecuSupQteRet){return response()->json(false);}
    

      
      
       foreach($ReceptionDets as $ReceptionDet){
        // Tester Si les valeurs saisie > reste && positive 
        $TTRec=$TTRec+$ReceptionDet['QteRecu'];
        $daarticle_AnNv=new \stdClass();

        $AncienQteRecu=cmd_reception_det::where('daarticle_id',$ReceptionDet['daarticle_id'])
        ->where('cmd_rec_id','=',$id)
        ->select('cmd_reception_dets.QteRecu')
        ->first();
        $AncienQteRecu=$AncienQteRecu->QteRecu;
        $AncienQteRecu=($AncienQteRecu*1)+0;

           $Reste= cmd_daarticle::where('daarticle_id','=',$ReceptionDet['daarticle_id'])
           ->join('cmds', function ($join) {
            $join->on('cmds.CommandeID', '=', 'cmd_daarticles.commande_id')
            ->where('cmds.exist','=',1);
           }) // tasli7
           ->select('cmd_daarticles.QteCmde','cmd_daarticles.TTQteRecu')
           ->first();
           $Reste=$Reste->QteCmde - $Reste->TTQteRecu + $AncienQteRecu ;
    
           $Reste=$this->MathRound((($Reste*1)+0)); 
           $ReceptionDet['QteRecu']= $this->MathRound((($ReceptionDet['QteRecu']*1)+0));
          

           if( ( $ReceptionDet['QteRecu'] > $Reste )   || $ReceptionDet['QteRecu']<0 ) {
            $isValid=false;
           }

        $daarticle_AnNv->daarticle_id=$ReceptionDet['daarticle_id'];
        $daarticle_AnNv->An=$AncienQteRecu;
        $daarticle_AnNv->Nv=$ReceptionDet['QteRecu'];
        if($daarticle_AnNv->An != $daarticle_AnNv->Nv){array_push($AnNvs,$daarticle_AnNv);}

         // Tesetr if stock reel ywallich fil moins ?!
         $ArticleID=$this->GetArticleIDByDaarticleid($ReceptionDet['daarticle_id']);
         if($ArticleID){
         $Qte=$ReceptionDet['QteRecu'] - $AncienQteRecu;
         $TesTLogiqueStockNotInfAZero=$this->TesTLogiqueStockNotInfAZero($ArticleID,$Qte);
         if(!$TesTLogiqueStockNotInfAZero){$isValid=false;}
         }




      }
      

       //###################################################



    if($TTRec==0){$TypeDeModif='supression';}
    if(!$isValid){return response()->json($isValid);}
   
       $BonDet=$this->GetBonDetByCmdRecID($id); // for the pump 
       foreach($ReceptionDets as $ReceptionDet){

        $AncienQteRecu=cmd_reception_det::where('daarticle_id',$ReceptionDet['daarticle_id'])
        ->where('cmd_rec_id','=',$id)
        ->select('cmd_reception_dets.QteRecu')
        ->first();
        $AncienQteRecu=$AncienQteRecu->QteRecu;
        $AncienQteRecu= ($AncienQteRecu*1)+0 ;
       
        $retour1=cmd_reception_det::where('daarticle_id','=',$ReceptionDet['daarticle_id'])
        ->where('cmd_rec_id','=',$id)
        ->update(array('QteRecu' => $ReceptionDet['QteRecu']));
        
        //return response()->json($ReceptionDet['daarticle_id'].':'.$AncienQteRecu.','.$ReceptionDet['QteRecu']);

        if($retour1){
        $retour2=cmd_daarticle::where('daarticle_id',$ReceptionDet['daarticle_id'])
        ->join('cmds', function ($join) {
          $join->on('cmds.CommandeID', '=', 'cmd_daarticles.commande_id')
          ->where('cmds.exist','=',1);
        }) // tasli7 
        ->increment('TTQteRecu',( $ReceptionDet['QteRecu'] - $AncienQteRecu ) );

        
        
        $ArticleID=$this->GetArticleIDByDaarticleid($ReceptionDet['daarticle_id']);
        if($ArticleID){

        // Mouvement du stock // amelioration
        // Insertion dans tableau mouvements

        $Qte=$ReceptionDet['QteRecu'] - $AncienQteRecu;
        $Ret_UpdateStock=$this->UpdateStockByArticleID($ArticleID,$Qte);
        if(!$Ret_UpdateStock){
          $success=false;
        }
        
        // PUMP
if($Qte!=0){
  if($BonDet){
    $TypeID='CmdRecID';
    $ID=$id;
    if($Qte>0){
      $type='increaseReception';
      //$Qte2=$Qte;
    }
    if($Qte<0){
      $type='decreaseReception';
      //$Qte2=-$Qte;
    }
    $Prix=$this->GetPrixArticleByBonDetAndArticleID($BonDet,$ArticleID);
    $retPUMP=$this->PUMP($ArticleID,$TypeID,$ID,$type,$Qte,$Prix);
    if(!$retPUMP){$success=false;}
}
}
        //####

        }else{
          $success=true;
        }
        //###################################


        }


        if( ! ( ($ReceptionDet['QteRecu']>=0) && $retour1 && $retour2 )  ){
          $success=false;
        }

        }

        if($success){
          $retourModif = cmd_reception_modif::create([
            'cmd_rec_id'=>$id,
            'user_id'=>$UserID,
            'TypeDeModif'=>$TypeDeModif,
            'rapport'=>$rapport
          ]);
          $CmdRecModifID=$retourModif->CmdRecModifID;
          if($retourModif){
            foreach($AnNvs as $AnNv){
              $retourModifDet = cmd_reception_modif_det::create([
                'cmd_rec_modif_id'=>$CmdRecModifID,
                'daarticle_id'=>$AnNv->daarticle_id,
                'AnQteRecu'=>$AnNv->An,
                'NvQteRecu'=>$AnNv->Nv
              ]);
              if(!$retourModifDet){$success=false;}
            }

            cmd_reception::where('CmdRecID','=',$id)->update(['isModified'=>1]);
            cmd_reception::where('CmdRecID','=',$id)->update(['rapport'=>$rapport]);
            cmd_reception::where('CmdRecID','=',$id)->increment('NbDeModif',1);
           

          }else{
            $success=false;
           // return response()->json('retour :'.$retourModif);
          }
        }
        
    return response()->json($success);
  }


  public function GetArticleIDByDaarticleid($daarticle_id){
    $Ret_ArticleID=daarticle::where('id','=',$daarticle_id)
        ->select('daarticles.article_id')
        ->first();
    if($Ret_ArticleID){
      $ArticleID=$Ret_ArticleID->article_id;
      return $ArticleID;
    }else{
     return false;
    }
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

  public function GetStockByArticleID($ArticleID){
    $article=article::where('ArticleID','=',$ArticleID)->select('stock')->first();
    if($article){
     $stock=$article->stock;
     return $stock;
    }else{
     return 'error';
    }
  }


  public function recmodifs(Request $request,$id){
    return cmd_reception_modif::where('cmd_rec_id','=',$id)
    ->join('users', function ($join) {
       $join->on('users.id', '=', 'cmd_reception_modifs.user_id');
   })
   ->select('cmd_reception_modifs.*','users.name')
    ->get();
  }

  public function getmodif(Request $request,$id){
    $cmd_reception_modif=cmd_reception_modif::where('CmdRecModifID','=',$id)
    ->join('users', function ($join) {
      $join->on('users.id', '=', 'cmd_reception_modifs.user_id');
    })
    ->join('cmd_receptions', function ($join) {
      $join->on('cmd_receptions.CmdRecID', '=', 'cmd_reception_modifs.cmd_rec_id');
    })
    ->select('cmd_reception_modifs.*','users.name','cmd_receptions.commande_id')
    ->first();

    $CmdRecModifID=$cmd_reception_modif->CmdRecModifID;
    if($cmd_reception_modif){
      $cmd_reception_modif_det=cmd_reception_modif_det::where('cmd_rec_modif_id','=',$CmdRecModifID)
      ->join('daarticles', function ($join) {
        $join->on('daarticles.id', '=', 'cmd_reception_modif_dets.daarticle_id');
      })
      ->join('articles', function ($join) {
        $join->on('articles.ArticleID', '=', 'daarticles.article_id');
      })
      ->join('cmd_reception_modifs', function ($join) {
        $join->on('cmd_reception_modifs.CmdRecModifID', '=', 'cmd_reception_modif_dets.cmd_rec_modif_id');
      })
      ->join('users', function ($join) {
        $join->on('users.id', '=', 'cmd_reception_modifs.user_id');
      })
      ->select('cmd_reception_modif_dets.*','articles.des','articles.ArticleID','users.name')
      ->get();
    }

    return response()->json(['modif'=>$cmd_reception_modif,'modif_det'=>$cmd_reception_modif_det]);
  }
  

  public function fermercmd(Request $request,$id){
    $ret = cmd::where('CommandeID','=',$id)->where('exist','=',1)->where('statut','<>','vide')->update(['statut'=>'ferme']);
    if($ret){$this->ChangementsNecessaireApresFermetureCmd($id);}
    return $ret;
  }

  public function ouvrircmd(Request $request,$id){
    // nrmalement nthabbot statut ancien kénou vide donc ma yetbadalch ouvert // securité backend 
    $ret = cmd::where('CommandeID','=',$id)->where('exist','=',1)->where('statut','<>','vide')->update(['statut'=>'ouvert']);
    if($ret){
      $da_ids=$this->CmdDasIds($id);
         foreach($da_ids as $da_id){
             $DaID=$da_id->id;
             $this->StatutDaToCommandeByDaID($DaID);
         }
      }
    return $ret;
  }

  public function droitrestaurationcmd(Request $request,$id,$source='frontEnd'){
    $droit=false;
    $daarticles_ids_cmde = $this->DaArtcileDeCmdEnCmd($id);
    $count = count($daarticles_ids_cmde);
    if($count==0){ $droit=true;}
    if($source=='restaurercmd'){return count($daarticles_ids_cmde);}
    return response()->json($daarticles_ids_cmde);
  }


  public function fournisseurcmds(Request $request,$id){
    $start = new Carbon('first day of last month'); $start->hour(0)->minute(0)->second(0);
    
    return cmd::where('fournisseur_id','=',$id)->where('exist','=',1)
    ->whereDate('created_at','>=',$start)
    ->orderBy('cmds.created_at', 'desc')
    ->get();
  }

  public function imprimer(Request $request){
    $success=true;
    $CmdIds=$request->input('CmdIds');
    $TTArticles=Array();

    $nom=cmd::where('CommandeID','=',$CmdIds[0])
    ->join('fournisseurs', function ($join) {
      $join->on('fournisseurs.id', '=', 'cmds.fournisseur_id');
    })
    ->select('fournisseurs.nom')
    ->first();
    $nom=$nom->nom;
 
    foreach($CmdIds as $CmdId){
      $ret=cmd_daarticle::where('commande_id','=',$CmdId)
      ->join('daarticles', function ($join) {
        $join->on('daarticles.id', '=', 'cmd_daarticles.daarticle_id');
      })
      ->join('articles', function ($join) {
        $join->on('articles.ArticleID', '=', 'daarticles.article_id');
      })
      ->select('articles.ArticleID','articles.des','cmd_daarticles.QteCmde')
      ->get();

      
      foreach($ret as $article){
        //array_push($TTArticles,$article);
        if( isset($TTArticles[$article->ArticleID]) ){
          $value=(object)$TTArticles[$article->ArticleID];
          $value->QteCmde+=$article->QteCmde;
          $TTArticles[$article->ArticleID]=$value;
        }
        else{
          $TTArticles[$article->ArticleID]=$article;
        }
      }
    }
   
   return response()->json(['CmdIds'=>$CmdIds,'CmdArticles'=>$TTArticles,'nom'=>$nom]);
  

  }


 public function allreceptions(){
  $start = new Carbon('first day of last month'); $start->hour(0)->minute(0)->second(0);
  return cmd_reception::
  where('cmd_receptions.created_at','>=',$start)
  ->join('users', function ($join) {
     $join->on('users.id', '=', 'cmd_receptions.user_id');
    })
  ->select('cmd_receptions.*','users.name')
  ->orderBy('cmd_receptions.created_at', 'desc')
  ->get();

 }

 public function allmodifs(){
  $start = new Carbon('first day of last month'); $start->hour(0)->minute(0)->second(0);
  return cmd_reception_modif::
  where('cmd_reception_modifs.created_at','>=',$start)
  ->join('users', function ($join) {
     $join->on('users.id', '=', 'cmd_reception_modifs.user_id');
    })
  ->select('cmd_reception_modifs.*','users.name')
  ->orderBy('cmd_reception_modifs.created_at', 'desc')
  ->get();
 }




  public function cmdfilter(Request $request,$exist){
    
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

    $ouvert=$filter['ouvert']; if($ouvert){$ouvert='true';}
    $ferme=$filter['ferme']; if($ferme){$ferme='true';}
    $vide=$filter['vide']; if($vide){$vide='true';}
    
    $UserID = JWTAuth::user()->id;
    $me = app('App\Http\Controllers\Divers\generale')->getUserPosts($UserID);
    $me = $me->original;
    $me = $me[0];
    $posts = $me->posts;

    $isAdmin = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, ['Admin']);
    $isMethode = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, ['Methode']);
    $OthersAuthorized = ['ChefDeEquipe', 'ChefDePoste', 'ResponsableMaintenance'];
    $isOthersAuthorized = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, $OthersAuthorized);

    /*
    $ouvert=$request->input('ouvert');
    $ferme=$request->input('ferme');
    $vide=$request->input('vide');
    $searchFilterText=$request->input('searchFilterText');
    $datemin=$request->input('datemin');
    $datemax=$request->input('datemax');
    $returnArr=Array();
    $start = new Carbon('first day of last month'); $start->hour(0)->minute(0)->second(0);
    
    if($datemin!=''){
       $datemin= Carbon::parse($datemin);
       $datemin->hour(0)->minute(0)->second(0);
    }else{$datemin=$start;}
    if($datemax!=''){ 
      $datemax= Carbon::parse($datemax);
      $datemax->hour(0)->minute(0)->second(0);
      $datemax->addDays(1);
    }else{$datemax=Carbon::parse('2120-01-01 00:00:00');}
    */

 $cmds=cmd::
 where('cmds.exist','=',$exist)
 ->whereDate('cmds.created_at','>=',$datemin)
 ->whereDate('cmds.created_at','<=',$datemax)
// ->orWhere('cmds.CommandeID','like','%'.$searchFilterText.'%')
 ->Where(function ($query) use($ouvert,$ferme,$vide) {
   return $query
   ->Where(function ($query) use($ouvert) {
      if( ( $ouvert=='true' ) ){ return $query->where('cmds.statut','=', 'ouvert'); }else{return $query->where('cmds.statut','=', 'rien');}
    })
    ->orWhere(function ($query) use($ferme) {
      if( ( $ferme=='true' ) ){ return $query->where('cmds.statut','=', 'ferme'); }else{return $query->where('cmds.statut','=', 'rien');}
    })
    ->orWhere(function ($query) use($vide) {
      if( ( $vide=='true' ) ){ return $query->where('cmds.statut','=', 'vide'); }else{return $query->where('cmds.statut','=', 'rien');}
    });
  })
  ->join('cmd_daarticles', function ($join) use($searchFilterText) {
    $join->on('cmd_daarticles.commande_id', '=', 'cmds.CommandeID');
    })
  ->join('fournisseurs', function ($join) use($searchFilterText) {
        $join->on('fournisseurs.id', '=', 'cmds.fournisseur_id');
    })
  ->join('users', function ($join) use($searchFilterText) {
        $join->on('users.id', '=', 'cmds.user_id');
    })
  ->join('daarticles', function ($join) use($searchFilterText) {
      $join->on('daarticles.id', '=', 'cmd_daarticles.daarticle_id');
    })
  ->join('articles', function ($join) use($searchFilterText) {
      $join->on('articles.ArticleID', '=', 'daarticles.article_id');
    })
  ->Where(function ($query) use($searchFilterText){
      return $query   
            ->where('users.name','like','%'.$searchFilterText.'%')
            ->orWhere('fournisseurs.nom','like','%'.$searchFilterText.'%')
            ->orWhere('articles.des','like','%'.$searchFilterText.'%')
            ->orWhere('articles.code_article','like','%'.$searchFilterText.'%')
            ->orWhere('cmds.CommandeID','like','%'.$searchFilterText.'%')
            ->orWhere('articles.code_a_barre','like','%'.$searchFilterText.'%');
   })
  
  ->select('cmds.*','fournisseurs.nom','users.name')
  ->orderBy('cmds.created_at', 'desc')
  //->groupBy('cmds.CommandeID')
  ->distinct('cmds.CommandeID');
  //->get();

  //return response()->json($cmds);
  $countQuery = $cmds->count();
  $cmds = $cmds->skip($skipped)->take($itemsPerPage)->get();
  return response()->json(['cmds' => $cmds, 'me' => $me, 'total' => $countQuery]);

  }



  

  public function imprimercmdfilter(Request $request,$id){
    
    $ouvert=$request->input('ouvert');
    $ferme=$request->input('ferme');
    $vide=$request->input('vide');
    $searchFilterText=$request->input('searchFilterText');
    $datemin=$request->input('datemin');
    $datemax=$request->input('datemax');
    $returnArr=Array();
    $start = new Carbon('first day of last month'); $start->hour(0)->minute(0)->second(0);

    if($datemin!=''){
       $datemin= Carbon::parse($datemin);
       $datemin->hour(0)->minute(0)->second(0);
    }else{$datemin=$start;}
    if($datemax!=''){ 
      $datemax= Carbon::parse($datemax);
      $datemax->hour(0)->minute(0)->second(0);
      $datemax->addDays(1);
    }else{$datemax=Carbon::parse('2120-01-01 00:00:00');}
    
 $cmds=cmd::
 where('cmds.exist','=',1)
 ->whereDate('cmds.created_at','>',$datemin)
 ->whereDate('cmds.created_at','<=',$datemax)
// ->orWhere('cmds.CommandeID','like','%'.$searchFilterText.'%')
 ->Where(function ($query) use($ouvert,$ferme,$vide) {
   return $query
   ->Where(function ($query) use($ouvert) {
      if( ( $ouvert=='true' ) ){ return $query->where('cmds.statut','=', 'ouvert'); }else{return $query->where('cmds.statut','=', 'rien');}
    })
    ->orWhere(function ($query) use($ferme) {
      if( ( $ferme=='true' ) ){ return $query->where('cmds.statut','=', 'ferme'); }else{return $query->where('cmds.statut','=', 'rien');}
    })
    ->orWhere(function ($query) use($vide) {
      if( ( $vide=='true' ) ){ return $query->where('cmds.statut','=', 'vide'); }else{return $query->where('cmds.statut','=', 'rien');}
    });
  })
  ->join('cmd_daarticles', function ($join) use($searchFilterText) {
    $join->on('cmd_daarticles.commande_id', '=', 'cmds.CommandeID');
    })
  ->join('fournisseurs', function ($join) use($searchFilterText,$id) {
        $join->on('fournisseurs.id', '=', 'cmds.fournisseur_id')
        ->where('fournisseurs.id','=',$id);
    })
  ->join('users', function ($join) use($searchFilterText) {
        $join->on('users.id', '=', 'cmds.user_id');
    })
  ->join('daarticles', function ($join) use($searchFilterText) {
      $join->on('daarticles.id', '=', 'cmd_daarticles.daarticle_id');
    })
  ->join('articles', function ($join) use($searchFilterText) {
      $join->on('articles.ArticleID', '=', 'daarticles.article_id');
    })
  ->Where(function ($query) use($searchFilterText){
      return $query   
            ->where('users.name','like','%'.$searchFilterText.'%')
            ->orWhere('fournisseurs.nom','like','%'.$searchFilterText.'%')
            ->orWhere('articles.des','like','%'.$searchFilterText.'%')
            ->orWhere('articles.code_article','like','%'.$searchFilterText.'%')
            ->orWhere('cmds.CommandeID','like','%'.$searchFilterText.'%')
            ->orWhere('articles.code_a_barre','like','%'.$searchFilterText.'%');
   })
  
  ->select('cmds.*','fournisseurs.nom','users.name')
  ->orderBy('cmds.created_at', 'desc')
  //->groupBy('cmds.CommandeID')
  ->distinct('cmds.CommandeID')
  ->get();

  return response()->json($cmds);

  }

  public function receptionfilter(Request $request){
    
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

    $isModifed=$filter['isModified']; if($isModifed){$isModifed='true';}
    $isPropre=$filter['isPropre']; if($isPropre){$isPropre='true';}
    
    $UserID = JWTAuth::user()->id;
    $me = app('App\Http\Controllers\Divers\generale')->getUserPosts($UserID);
    $me = $me->original;
    $me = $me[0];
    $posts = $me->posts;

    $isAdmin = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, ['Admin']);
    $isMethode = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, ['Methode']);
    $OthersAuthorized = ['ChefDeEquipe', 'ChefDePoste', 'ResponsableMaintenance'];
    $isOthersAuthorized = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, $OthersAuthorized);

    $returnArr=Array();


   /*
    $isModifed=$request->input('isModified');
    $isPropre=$request->input('isPropre');
    $searchFilterText=$request->input('searchFilterText');
    $datemin=$request->input('datemin');
    $datemax=$request->input('datemax');
    $returnArr=Array();
    $start = new Carbon('first day of last month'); $start->hour(0)->minute(0)->second(0);
  

    if($datemin!=''){
       $datemin= Carbon::parse($datemin);
       $datemin->hour(0)->minute(0)->second(0);
    }else{$datemin=$start;}
    if($datemax!=''){ 
      $datemax= Carbon::parse($datemax);
      $datemax->hour(0)->minute(0)->second(0);
      $datemax->addDays(1);
    }else{$datemax=Carbon::parse('2120-01-01 00:00:00');}
  */

  // cmd_bon::where('cmd_rec_id','=',$id)->first();

 $cmd_receptions=cmd_reception::
   whereDate('cmd_receptions.created_at','>=',$datemin)
 ->whereDate('cmd_receptions.created_at','<=',$datemax)
 ->Where(function ($query) use($isModifed,$isPropre) {
    if( $isModifed== $isPropre){
      return $query
        ->where('cmd_receptions.isModified','=', 1)
      ->orWhere('cmd_receptions.isModified','=', 0);
    }
    else if (  $isModifed=='true' ){ return $query->where('cmd_receptions.isModified','=', 1); }
    else { return $query->where('cmd_receptions.isModified','=', 0); } 
      
  })
  ->join('cmd_reception_dets', function ($join) use($searchFilterText) {
    $join->on('cmd_reception_dets.cmd_rec_id', '=', 'cmd_receptions.CmdRecID');
  })
  ->join('daarticles', function ($join) use($searchFilterText) {
    $join->on('daarticles.id', '=', 'cmd_reception_dets.daarticle_id');
  })
  ->join('articles', function ($join) use($searchFilterText) {
    $join->on('articles.ArticleID', '=', 'daarticles.article_id');
  })
  
  ->join('cmds', function ($join) use($searchFilterText) {
      $join->on('cmds.CommandeID', '=', 'cmd_receptions.commande_id')
      ->where('cmds.exist','=',1); // tasli7
  })
  ->join('fournisseurs', function ($join) use($searchFilterText) {
        $join->on('fournisseurs.id', '=', 'cmds.fournisseur_id');
    })
  ->join('users', function ($join) use($searchFilterText) {
        $join->on('users.id', '=', 'cmd_receptions.user_id');
    })
  ->leftjoin('cmd_bons', function ($join) {
      $join->on('cmd_bons.cmd_rec_id', '=', 'cmd_receptions.CmdRecID');
  })
  
 
  ->Where(function ($query) use($searchFilterText){
      return $query   
            ->where('users.name','like','%'.$searchFilterText.'%')
            ->orWhere('fournisseurs.nom','like','%'.$searchFilterText.'%')
            ->orWhere('articles.des','like','%'.$searchFilterText.'%')
            ->orWhere('articles.code_article','like','%'.$searchFilterText.'%')
            ->orWhere('cmd_receptions.CmdRecID','like','%'.$searchFilterText.'%')
            ->orWhere('articles.code_a_barre','like','%'.$searchFilterText.'%');
   })
  ->select('cmd_receptions.*','users.name','cmd_bons.BonID')
  ->orderBy('cmd_receptions.created_at', 'desc')
  ->distinct('cmd_receptions.CmdRecID');
  //->get();

    
    $countQuery = $cmd_receptions->count();
    $cmd_receptions = $cmd_receptions->skip($skipped)->take($itemsPerPage)->get();
    return response()->json(['receptions' => $cmd_receptions, 'me' => $me, 'total' => $countQuery]);

    //return response()->json($cmd_receptions);

  }

  public function addbon(Request $request,$id){
    $success=true;
    $msg="Le bon est ajoutée avec succées .";

    $UserID=JWTAuth::user()->id;
    $bondets=$request->BonDet;

    $bon=cmd_bon::create([
      'user_id'=>$UserID,
      'cmd_rec_id'=>$id
    ]);
    $BonID=$bon->BonID;
    if($bon){

      foreach($bondets as $article){
        $articleObj=(object)$article;
        $AnPrixHT=$this->GetAnPrixHTByArticleID($articleObj->ArticleID);
        $bon_det=cmd_bon_det::create([
          'bon_id'=>$BonID,
          'QteBon'=>$articleObj->QteBon,
          'article_id'=>$articleObj->ArticleID,
          'PrixHT'=>$articleObj->PrixHT,
          'AnPrixHT'=>$AnPrixHT,
        ]);
        
        if($bon_det){
          $QteRecu=$this->GetTTRecArticleByCmdRecIDAndArticleID($id,$articleObj->ArticleID);
          if($QteRecu!=false){
          $type='addBon';
          $TypeID='BonID';
          $ID=$BonID;
          //='.$articleObj.')(
          //error::insert(['error'=>'1:addbon']);
          //error::insert(['error'=>'PUMP( $articleObj->ArticleID='.$articleObj->ArticleID.')($TypeID='.$TypeID.')($ID='.$ID.')($type='.$type.')($QteRecu='.$QteRecu.')($articleObj->PrixHT='.$articleObj->PrixHT.') )']);
          
          $retPUMP=$this->PUMP($articleObj->ArticleID,$TypeID,$ID,$type,$QteRecu,$articleObj->PrixHT);
          if(!$retPUMP){$success=false;}
          }else{
            $success=false; $msg="Error .";
          }
        }else{
          $success=false; $msg="Error .";
        }
      } 
      
      $AddOrDelete="add";
      $this->PUMPAllRetoursByCmdRecID($id,$AddOrDelete);
     
      /*
      if(!$success){
        cmd_bon_det::where('bon_id','=',$BonID)->delete();
        cmd_bon::where('BonID','=',$BonID)->delete();
      }
      */

    }else{$success=false; $msg="Error .";}

    //return response()->json($success);
    
    return response()->json(['success'=>$success,'msg'=>$msg,'BonID'=>$BonID]);

  }

  

  public function GetAnPrixHTByArticleID($ArticleID){
    $cmd_bon_det=cmd_bon_det::where('article_id','=',$ArticleID)
    ->orderBy('cmd_bon_dets.created_at', 'desc')
    ->first();
    if($cmd_bon_det){
     $AnPrixHT=$cmd_bon_det->PrixHT;
     return $AnPrixHT;
    }
    return 0;
  }
  

  
  public function GetTTRecArticleByCmdRecIDAndArticleID($CmdRecID,$ArticleID){
    $cmd_reception_dets=cmd_reception_det::
    join('daarticles', function ($join){
      $join->on('daarticles.id', '=', 'cmd_reception_dets.daarticle_id');
    })
    ->where('cmd_reception_dets.cmd_rec_id','=',$CmdRecID)
    ->where('daarticles.article_id','=',$ArticleID)
    ->select(DB::raw('SUM(cmd_reception_dets.QteRecu)'))
    ->first();
    $field='SUM(cmd_reception_dets.QteRecu)';
    $QteRecu=$cmd_reception_dets->$field;
    if($cmd_reception_dets){
    return $QteRecu;
    }else{
      return false;
    }
  }

  public function PUMPAllRetoursByCmdRecID($CmdRecID,$AddOrDelete){
   $success=true;
   $cmd_retours=cmd_retour::where('cmd_rec_id','=',$CmdRecID)->get();
   foreach($cmd_retours as $cmd_retour){
    $RetourID=$cmd_retour['RetourID'];
    $Retou_RetourPUMP=$this->RetourPUMP($RetourID,$AddOrDelete);
    if(!$Retou_RetourPUMP){$success=false;}
   }
   return $success;
  }

  public function PUMP($ArticleID,$TypeID,$ID,$type,$NvQte,$NvPrixHT){
    $success=true;

    $art_pps=art_pp::where('article_id','=',$ArticleID)->where('exist','=',true)->get();
    
    /*
    $An=article::where('ArticleID','=',$ArticleID)->select('articles.stock','articles.PrixHT')->first();
    $PUMP=$An->PrixHT;
    $AnStock=$An->stock;
    */

  //if($art_pps && $An){

  if($art_pps){
  
 // return $NvQte;//12345

    //='.$articleObj.')(
    // error::insert(['error'=>'2:PUMP']);
    // error::insert(['error'=>$art_pps]);
    // error::insert(['error'=>'CalculerPUMP ($art_pps , $NvQte='.$NvQte.') ($NvPrixHT='.$NvPrixHT.') ) ']);

  $retourPUMP=$this->CalculerPUMP($art_pps,$NvQte,$NvPrixHT);
 // return $retourPUMP;//12345

  $AnPUMP=$retourPUMP['AnPUMP'];
  $NvPUMP=$retourPUMP['NvPUMP'];
  
  //if($this->MathRound4($PUMP)==$this->MathRound4($AnPUMP)){

   $art_pps_create=art_pp::create(['article_id'=>$ArticleID,'TypeID'=>$TypeID,'ID'=>$ID,'type'=>$type,
  'NvQte'=>$NvQte,'NvPrixHT'=>$NvPrixHT,'AnPUMP'=>$AnPUMP,'NvPUMP'=>$NvPUMP,'exist'=>1]);

  if($art_pps_create){
   $update=article::where('ArticleID','=',$ArticleID)->update(['PrixHT'=>$NvPUMP]);
   if(!$update){
    $success=false;
   }
  }else{
    $success=false;
  }
//}else{
  //$success=false;
//}

   
  }else{
    $success=false;
  }

  return $success;
  }

  public function CalculerPUMP($art_pps,$Qte,$PrixHT){
    $TTPrxiHT=0;
    $TTQte=0;
    foreach($art_pps as $art_pp){
      $NvQte=$art_pp['NvQte']; $NvPrixHT=$art_pp['NvPrixHT'];
      $TTPrxiHT+=$NvQte*$NvPrixHT;
      $TTQte+=$NvQte;
    }
    $AnPUMP=$TTPrxiHT/$TTQte; ///////////// division par zero 
    if($Qte==0){
    $NvPUMP=$AnPUMP;
    }else{
    $TTPrxiHT+=$Qte*$PrixHT;
    $TTQte+=$Qte;
    $NvPUMP=$TTPrxiHT/$TTQte;
    }
    return ['AnPUMP'=>$AnPUMP,'NvPUMP'=>$NvPUMP] ;
    }


  public function getbon(Request $request,$id){

    $bon=cmd_bon::where('BonID','=',$id)
    ->join('users', function ($join){
      $join->on('users.id', '=', 'cmd_bons.user_id');
    })
    ->select('cmd_bons.*','users.name')
    ->first();

    if($bon){
    $bon_det=cmd_bon_det::where('bon_id','=',$id)->get();
    return response()->json(['isDeleted'=>0,'bon'=>$bon,'bon_det'=>$bon_det]);
    }

    $bon=cmd_corb_bon::where('BonID','=',$id)
    ->join('users', function ($join){
      $join->on('users.id', '=', 'cmd_corb_bons.user_id');
    })
    ->select('cmd_corb_bons.*','users.name')
    ->first();

    if($bon){
    $bon_det=cmd_corb_bon_det::where('bon_id','=',$id)->get();
    return response()->json(['isDeleted'=>1,'bon'=>$bon,'bon_det'=>$bon_det]);
    }

   }


   
  public function getbonbycmdrecid(Request $request,$id){
   $bon=cmd_bon::where('cmd_rec_id','=',$id)->first();
   if($bon){
   $bon_det=cmd_bon_det::where('bon_id','=',$bon->BonID)->get();
   }else{
   return response()->json(false);
   }
   return response()->json(['bon'=>$bon,'bon_det'=>$bon_det]);
  }

  

  public function suprimerBon($id){
   $success=true;
   $cmd_bon=cmd_bon::where('BonID','=',$id)->first();
   $isFactured=$cmd_bon->isFactured;
  
  if(!$isFactured){
    //12345
   $INSERT_cmd_corb_bon=cmd_corb_bon::insert($cmd_bon->toArray());
   if(!$INSERT_cmd_corb_bon){$success=false;}
   
   $cmd_bon_dets=cmd_bon_det::where('bon_id','=',$id)->get();
   foreach($cmd_bon_dets as $cmd_bon_det){
    $INSET_cmd_corb_bon_det=cmd_corb_bon_det::insert($cmd_bon_det->toArray());
    if(!$INSET_cmd_corb_bon_det){$success=false; }
   }


  // $CmdRecID=$cmd_bon->cmd_rec_id;
  // $AddOrDelete="delete";
  // $this->PUMPAllRetoursByCmdRecID($CmdRecID,$AddOrDelete);
   //if(!$Retour_PUMPAllRetoursByCmdRecID){$success=false;}

   if($success){
    $ch=$this->ChangementNecessaireSupressionDeBon($id);
    if(!$ch){
      $success=false;
    }
   }

   //return response()->json($ch);//12345

   if($success){
    $DELETE_cmd_bon_dets=cmd_bon_det::where('bon_id','=',$id)->delete();
    $DELETE_cmd_bon=cmd_bon::where('BonID','=',$id)->delete();
    if(!$DELETE_cmd_bon || !$DELETE_cmd_bon_dets){$success=false;}
   }
  
  }else{$success=false;}

   return response()->json($success);

  }


  public function ChangementNecessaireSupressionDeBon($id){
    $success=true;
    $bondets=cmd_bon_det::where('bon_id','=',$id)->get();

    $Ret_CmdRecID=cmd_bon::where('BonID','=',$id)->first();
    $CmdRecID=$Ret_CmdRecID->cmd_rec_id;
    
     //1
   $AddOrDelete="delete";
   $PUMPAllRetoursByCmdRecID=$this->PUMPAllRetoursByCmdRecID($CmdRecID,$AddOrDelete);
     //2
   //$PUMPAllModifsByCmdRecID=$this->PUMPAllModifsByCmdRecID($CmdRecID);
   //return $PUMPAllModifsByCmdRecID;//12345
     //3
    foreach($bondets as $article){
      $articleObj=(object)$article;
        
        $QteRecu=$this->GetTTRecArticleByCmdRecIDAndArticleID($CmdRecID,$articleObj->article_id);
    
        if($QteRecu!=false){
        $type='deleteBon';
        $TypeID='BonID';
        $ID=$id;
        $Qte=-$QteRecu;
        $retPUMP=$this->PUMP($articleObj->article_id,$TypeID,$ID,$type,$Qte,$articleObj->PrixHT);
        if(!$retPUMP){$success=false;}
        }else{
          $success=false;
        }
    } 
   return $success;
  }

  public function PUMPAllModifsByCmdRecID($CmdRecID){
    
    $success=true;
    $cmd_reception_modifs=cmd_reception_modif::where('cmd_rec_id','=',$CmdRecID)->get();
    foreach($cmd_reception_modifs as $cmd_reception_modif){
     $CmdRecModifID=$cmd_reception_modif['CmdRecModifID'];
     $Retour_ModifReceptionPUMP=$this->ModifReceptionPUMP($CmdRecModifID);
     //return $Retour_ModifReceptionPUMP;//12345
     if(!$Retour_ModifReceptionPUMP){$success=false;}
    }
    return $success;

  }

  public function ModifReceptionPUMP($CmdRecModifID){

    $success=true;
    $cmd_reception_modif=cmd_reception_modif::where('CmdRecModifID','=',$CmdRecModifID)->first();
    $CmdRecID=$cmd_reception_modif->cmd_rec_id;
  
    $cmd_reception_modif_dets=cmd_reception_modif_det::where('cmd_rec_modif_id','=',$CmdRecModifID)->get();
    if(!$cmd_reception_modif_dets){$success=false;}
  
    $BonDet=$this->GetBonDetByCmdRecID($CmdRecID);

    if($BonDet){
    foreach($cmd_reception_modif_dets as $article){

      $daarticle_id=$article['daarticle_id'];
      $ArticleID=$this->GetArticleIDByDaarticleid($daarticle_id);
        if($ArticleID){


          $AnQteRecu=$article['AnQteRecu'];
          $NvQteRecu=$article['NvQteRecu'];
          $Qte=$NvQteRecu-$AnQteRecu;

          if($Qte!=0){
            if($BonDet){
              $TypeID='CmdRecID';
              $ID=$CmdRecID;
              if($Qte>0){
                $type='decreaseReception';
                //$Qte2=$Qte;
              }
              if($Qte<0){
                
                $type='increaseReception';
                //$Qte2=-$Qte;
              }
              $Prix=$this->GetPrixArticleByBonDetAndArticleID($BonDet,$ArticleID);
              $retPUMP=$this->PUMP($ArticleID,$TypeID,$ID,$type,-$Qte,$Prix);
              return $retPUMP;
              if(!$retPUMP){$success=false;}
          }
          }

        }

  }
   
    }

    return $success;
  }

  public function bons($corbeille){
    $start = new Carbon('first day of last month'); $start->hour(0)->minute(0)->second(0);
    if($corbeille==1){
      $bons=cmd_corb_bon::whereDate('cmd_corb_bons.created_at','>=',$start)
      ->orderBy('cmd_corb_bons.created_at', 'desc')
      ->get();
    }
    else{
      $bons=cmd_bon::whereDate('cmd_bons.created_at','>=',$start)
      ->orderBy('cmd_bons.created_at', 'desc')
      ->get();
    }
    return response()->json($bons);
  }

  
  public function filterbons(Request $request,$corbeille){

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

    $Factured=$filter['Factured']; if($Factured){$Factured='true';}
    $NonFactured=$filter['NonFactured']; if($NonFactured){$NonFactured='true';}
    
    $UserID = JWTAuth::user()->id;
    $me = app('App\Http\Controllers\Divers\generale')->getUserPosts($UserID);
    $me = $me->original;
    $me = $me[0];
    $posts = $me->posts;

    $isAdmin = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, ['Admin']);
    $isMethode = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, ['Methode']);
    $OthersAuthorized = ['ChefDeEquipe', 'ChefDePoste', 'ResponsableMaintenance'];
    $isOthersAuthorized = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, $OthersAuthorized);


    if($corbeille==0){
      $tab_bons='cmd_bons';
      $tab_bon_det='cmd_bon_dets';
    }else if($corbeille==1){
      $tab_bons='cmd_corb_bons';
      $tab_bon_det='cmd_corb_bon_dets';
    }else{
      return response()->json([]);
    }
     
    /*
      $Factured=$request->input('Factured');
      $NonFactured=$request->input('NonFactured');
      $searchFilterText=$request->input('searchFilterText');
      $datemin=$request->input('datemin');
      $datemax=$request->input('datemax');

      $start = new Carbon('first day of last month'); $start->hour(0)->minute(0)->second(0);
      
      if($datemin!=''){
         $datemin= Carbon::parse($datemin);
         $datemin->hour(0)->minute(0)->second(0);
      }else{$datemin=$start;}
      if($datemax!=''){ 
        $datemax= Carbon::parse($datemax);
        $datemax->hour(0)->minute(0)->second(0);
        $datemax->addDays(1);
      }else{$datemax=Carbon::parse('2120-01-01 00:00:00');}
  */

if($corbeille==0){
   $cmd_receptions=cmd_bon::
     whereDate($tab_bons.'.created_at','>=',$datemin)
   ->whereDate($tab_bons.'.created_at','<=',$datemax)
   ->Where(function ($query) use($Factured,$NonFactured,$tab_bons) {
      if( $Factured== $NonFactured){
         return $query
         ->where($tab_bons.'.isFactured','=', 1)
         ->orWhere($tab_bons.'.isFactured','=', 0);
      }
      else if (  $Factured=='true' ){ return $query->where($tab_bons.'.isFactured','=', 1); }
      else { return $query->where($tab_bons.'.isFactured','=', 0); } 
    })
   
    ->join($tab_bon_det, function ($join)use($tab_bons,$tab_bon_det){
      $join->on($tab_bon_det.'.bon_id', '=', $tab_bons.'.BonID');
    })
    ->join('articles', function ($join)use($tab_bon_det){
      $join->on('articles.ArticleID', '=', $tab_bon_det.'.article_id');
    })
    ->join('fournisseurs', function ($join){
          $join->on('fournisseurs.id', '=', 'articles.fournisseur_id');
      })
    ->join('users', function ($join)use($tab_bons){
          $join->on('users.id', '=', $tab_bons.'.user_id');
      })
    ->Where(function ($query) use($searchFilterText){
        return $query   
              ->where('users.name','like','%'.$searchFilterText.'%')
              ->orWhere('fournisseurs.nom','like','%'.$searchFilterText.'%')
              ->orWhere('articles.des','like','%'.$searchFilterText.'%')
              ->orWhere('articles.code_article','like','%'.$searchFilterText.'%')
              ->orWhere('articles.code_a_barre','like','%'.$searchFilterText.'%');
     })
    ->select($tab_bons.'.*','users.name')
    ->orderBy($tab_bons.'.created_at', 'desc')
    ->distinct($tab_bons.'.BonID');
    
    //->get();
    }

    if($corbeille==1){
      $cmd_receptions=cmd_corb_bon::
        whereDate($tab_bons.'.created_at','>=',$datemin)
      ->whereDate($tab_bons.'.created_at','<=',$datemax)
      ->Where(function ($query) use($Factured,$NonFactured,$tab_bons) {
         if( $Factured== $NonFactured){
            return $query
            ->where($tab_bons.'.isFactured','=', 1)
            ->orWhere($tab_bons.'.isFactured','=', 0);
         }
         else if (  $Factured=='true' ){ return $query->where($tab_bons.'.isFactured','=', 1); }
         else { return $query->where($tab_bons.'.isFactured','=', 0); } 
       })
      
       ->join($tab_bon_det, function ($join)use($tab_bons,$tab_bon_det){
         $join->on($tab_bon_det.'.bon_id', '=', $tab_bons.'.BonID');
       })
       ->join('articles', function ($join)use($tab_bon_det){
         $join->on('articles.ArticleID', '=', $tab_bon_det.'.article_id');
       })
       ->join('fournisseurs', function ($join){
             $join->on('fournisseurs.id', '=', 'articles.fournisseur_id');
         })
       ->join('users', function ($join)use($tab_bons){
             $join->on('users.id', '=', $tab_bons.'.user_id');
         })
       ->Where(function ($query) use($searchFilterText){
           return $query   
                 ->where('users.name','like','%'.$searchFilterText.'%')
                 ->orWhere('fournisseurs.nom','like','%'.$searchFilterText.'%')
                 ->orWhere('articles.des','like','%'.$searchFilterText.'%')
                 ->orWhere('articles.code_article','like','%'.$searchFilterText.'%')
                 ->orWhere('articles.code_a_barre','like','%'.$searchFilterText.'%');
        })
       ->select($tab_bons.'.*','users.name')
       ->orderBy($tab_bons.'.created_at', 'desc')
       ->distinct($tab_bons.'.BonID');
       
       //->get();
       }

    //return response()->json($cmd_receptions);

    $countQuery = $cmd_receptions->count();
    $cmd_receptions = $cmd_receptions->skip($skipped)->take($itemsPerPage)->get();
    return response()->json(['bons' => $cmd_receptions, 'me' => $me, 'total' => $countQuery]);
    
  }

public function getFoursHasNonFacturedBons(){
  return cmd_bon::
  where('cmd_bons.isFactured','=',0)
  ->join('cmd_receptions', function ($join){
    $join->on('cmd_receptions.CmdRecID', '=', 'cmd_bons.cmd_rec_id');
  })
  ->join('cmds', function ($join){
    $join->on('cmds.CommandeID', '=', 'cmd_receptions.commande_id')
    ->where('cmds.exist','=',1); // tasli7
  })
  ->join('fournisseurs', function ($join){
    $join->on('fournisseurs.id', '=', 'cmds.fournisseur_id');
  })
  ->select('fournisseurs.*')
  ->distinct('fournisseurs.id')
  ->get();

  

 }


 public function getFournisseurBons(Request $request,$id){
  // return bons
      $page=$request->input('page');
      $itemsPerPage=$request->input('itemsPerPage');
      //$nodes = $request->input('nodes');
      $skipped = ($page - 1) * $itemsPerPage;
      $endItem = $skipped + $itemsPerPage;

      $filter=$request->input('filter');
      $datemin=$filter['datemin'];
      $datemax=$filter['datemax'];

      if($datemin==''){$datemin=app('App\Http\Controllers\Divers\generale')->ReglageDateMinDateMax($datemin,'minimum');}
      else{$datemin=app('App\Http\Controllers\Divers\generale')->ReglageDateMinDateMax($datemin,'datemin');}

      //$datemin=app('App\Http\Controllers\Divers\generale')->ReglageDateMinDateMax($datemin,'minimum');
      $datemax=app('App\Http\Controllers\Divers\generale')->ReglageDateMinDateMax($datemax,'datemax');
      $searchFilterText=$filter['searchFilterText'];
      
      $UserID=JWTAuth::user()->id;
      $me=app('App\Http\Controllers\Divers\generale')->getUserPosts($UserID);
      $me=$me->original; $me=$me[0];
      $posts=$me->posts;
      
      $isAdmin=app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts,['Admin']);
      $isMethode=app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts,['Methode']);
      $OthersAuthorized=['ResponsableMagasin'];
      $isOthersAuthorized=app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts,$OthersAuthorized);

  $bons= cmd_bon::
  where('cmd_bons.isFactured','=',0)
  ->whereDate('cmd_bons.created_at','>=',$datemin)
  ->whereDate('cmd_bons.created_at','<=',$datemax)

  ->join('cmd_receptions', function ($join){
    $join->on('cmd_receptions.CmdRecID', '=', 'cmd_bons.cmd_rec_id');
  })
  ->join('cmds', function ($join) use($id){
    $join->on('cmds.CommandeID', '=', 'cmd_receptions.commande_id')
    ->where('cmds.exist','=',1) // tasli7
    ->where('cmds.fournisseur_id','=',$id);
  });

  //->get();
  
      $countQuery = $bons->count();
      $bons = $bons->skip($skipped)->take($itemsPerPage)->get();

    

    //return response()->json($fournisseurs);


      return response()->json(['bons'=>$bons,'me'=>$me,'total'=>$countQuery]);
 }



 public function addFouFilter(Request $request , $id){

    $searchFilterText=$request->input('searchFilterText');
    $datemin=$request->input('datemin');
    $datemax=$request->input('datemax');
    $start = new Carbon('first day of last month'); $start->hour(0)->minute(0)->second(0);

    if($datemin!=''){
       $datemin= Carbon::parse($datemin);
       $datemin->hour(0)->minute(0)->second(0);
    }else{$datemin=$start;}
    if($datemax!=''){ 
      $datemax= Carbon::parse($datemax);
      $datemax->hour(0)->minute(0)->second(0);
      $datemax->addDays(1);
    }else{$datemax=Carbon::parse('2120-01-01 00:00:00');}
    
 $bons=cmd_bon::
   whereDate('cmd_bons.created_at','>',$datemin)
 ->whereDate('cmd_bons.created_at','<=',$datemax)
->join('cmd_receptions', function ($join){
  $join->on('cmd_receptions.CmdRecID', '=', 'cmd_bons.cmd_rec_id');
})
->join('cmds', function ($join) use($id){
  $join->on('cmds.CommandeID', '=', 'cmd_receptions.commande_id')
  ->where('cmds.exist','=',1)
  ->where('cmds.fournisseur_id','=',$id);
})
  ->join('cmd_daarticles', function ($join) {
    $join->on('cmd_daarticles.commande_id', '=', 'cmds.CommandeID');
    })
  ->join('users', function ($join) {
        $join->on('users.id', '=', 'cmds.user_id');
    })
  ->join('daarticles', function ($join) {
      $join->on('daarticles.id', '=', 'cmd_daarticles.daarticle_id');
    })
  ->join('articles', function ($join){
      $join->on('articles.ArticleID', '=', 'daarticles.article_id');
    })
  ->Where(function ($query) use($searchFilterText){
      return $query   
            ->where('users.name','like','%'.$searchFilterText.'%')
            ->orWhere('articles.des','like','%'.$searchFilterText.'%')
            ->orWhere('articles.code_article','like','%'.$searchFilterText.'%')
            // ->orWhere('cmds.CommandeID','like','%'.$searchFilterText.'%')
            ->orWhere('articles.code_a_barre','like','%'.$searchFilterText.'%');
   })
  
  ->select('cmd_bons.*')
  ->orderBy('cmd_bons.created_at', 'desc')
  //->groupBy('cmds.CommandeID')
  ->distinct('cmd_bons.BonID')
  ->get();

  return response()->json($bons);
 }


 public function facturer(Request $request,$id){
  $success=true;
  $BonsIds=$request->input('BonsIds');
  $Ret_Nom=fournisseur::where('id','=',$id)->first();
  $nom=$Ret_Nom->nom;
  $facture=cmd_facture::create(['fournisseur_id'=>$id]);
  $FactureID=$facture->FactureID;
  $TTTTC=0;

  $CreateOrShow='create';
  $Result=$this->BonIdsToFacture($BonsIds,$FactureID,$CreateOrShow);
  $TTArticles=$Result['TTArticles'];
  $TTTTC=$Result['TTTTC'];

  $update=cmd_facture::where('FactureID','=',$FactureID)->update(['TTTTC'=>$TTTTC]);

  return response()->json(['FounisseurID'=>$id,'nom'=>$nom, 'FactureID'=>$FactureID,'BonsIds'=>$BonsIds,'FactureArticles'=>$TTArticles ]);

 } 

 public function getFacture($id){
   
  $Ret_Facture=cmd_facture::where('FactureID','=',$id)->first();
  $FournisseurID=$Ret_Facture->fournisseur_id;
  $Ret_Nom=fournisseur::where('id','=',$FournisseurID)->first();
  $nom=$Ret_Nom->nom;

  $ArrBonsIds=$this->GetBonIdsByFactureID($id);

  $FactureCanRestaured=false;
  if($Ret_Facture['exist']==false){$FactureCanRestaured=$this->FactureCanRestaured($id);}

  $CreateOrShow='show';
  $Result=$this->BonIdsToFacture($ArrBonsIds,$id,$CreateOrShow);
  $TTArticles=$Result['TTArticles'];
  return ['Facture'=>$Ret_Facture,'canRestaured'=>$FactureCanRestaured,'FournisseurID'=>$id,'nom'=>$nom,'BonsIds'=>$ArrBonsIds,'FactureArticles'=>$TTArticles];
 
}

public function GetBonIdsByFactureID($FactureID){
  $BonsIds=cmd_facture_det::where('facture_id','=',$FactureID)->select('bon_id')->get();
  $ArrBonsIds=Array();
  foreach($BonsIds as $BonsId){
   array_push($ArrBonsIds,$BonsId['bon_id']);
  };
  return $ArrBonsIds;
}

public function FactureCanRestaured($FactureID){
  $BonsIds=$this->GetBonIdsByFactureID($FactureID);
  $cmd_facture_dets=cmd_facture_det::whereIn('bon_id',$BonsIds)->where('facture_id','<>',$FactureID)->get();
  if(count($cmd_facture_dets)==0){
  return true;
  }else{

    foreach($cmd_facture_dets as $cmd_facture_det){
      $BonID=$cmd_facture_det['bon_id'];
      $cmd_bons=cmd_bon::where('BonID','=',$BonID)->select('isFactured')->first();
      $isFactured=$cmd_bons->isFactured;
      if($isFactured==true){return false;}
    }
    return true;
  }
}

public function restaurerFacture($id){
  $success=true;
  $BonsIds=$this->GetBonIdsByFactureID($id);
  foreach($BonsIds as $BonID){
    $cmd_bon=cmd_bon::where('BonID','=',$BonID)->update(['isFactured'=>true]);
  }
  $facture= cmd_facture::where('FactureID','=',$id)->update(['exist'=>true]);
  if(!$facture){$success=false;}
  return response()->json($success);
}


public function factures(){
  $factures=cmd_facture::where('cmd_factures.exist','=',1)
  ->join('fournisseurs', function ($join){
    $join->on('fournisseurs.id', '=', 'cmd_factures.fournisseur_id');
  })
  ->select('cmd_factures.*','fournisseurs.nom')
  ->get();
  return response()->json($factures);
}


public function factureFilter(Request $request,$exist){
    $exist=!$exist;
    
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

    $TTCmin=$filter['TTCmin'];
    $TTCmax=$filter['TTCmax'];

    //$isModifed=$filter['isModified']; if($isModifed){$isModifed='true';}
    //$isPropre=$filter['isPropre']; if($isPropre){$isPropre='true';}
    
    $UserID = JWTAuth::user()->id;
    $me = app('App\Http\Controllers\Divers\generale')->getUserPosts($UserID);
    $me = $me->original;
    $me = $me[0];
    $posts = $me->posts;

    $isAdmin = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, ['Admin']);
    $isMethode = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, ['Methode']);
    $OthersAuthorized = ['ChefDeEquipe', 'ChefDePoste', 'ResponsableMaintenance'];
    $isOthersAuthorized = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, $OthersAuthorized);

/*
  $searchFilterText=$request->input('searchFilterText');
  $datemin=$request->input('datemin');
  $datemax=$request->input('datemax');
  $TTCmin=$request->input('TTCmin');
  $TTCmax=$request->input('TTCmax');
  $start = new Carbon('first day of last month'); $start->hour(0)->minute(0)->second(0);
  
  if($datemin!=''){
     $datemin= Carbon::parse($datemin);
     $datemin->hour(0)->minute(0)->second(0);
  }else{$datemin=$start;}
  if($datemax!=''){ 
    $datemax= Carbon::parse($datemax);
    $datemax->hour(0)->minute(0)->second(0);
    $datemax->addDays(1);
  }else{$datemax=Carbon::parse('2120-01-01 00:00:00');}
 */

$cmd_factures=cmd_facture::
 where('cmd_factures.exist','=',$exist)
->whereDate('cmd_factures.created_at','>',$datemin)
->whereDate('cmd_factures.created_at','<=',$datemax)

->join('cmd_facture_dets', function ($join) {
  $join->on('cmd_facture_dets.facture_id', '=', 'cmd_factures.FactureID');
})
->join('cmd_bon_dets', function ($join)  {
  $join->on('cmd_bon_dets.bon_id', '=', '.cmd_facture_dets.bon_id');
})
->join('articles', function ($join) {
  $join->on('articles.ArticleID', '=', 'cmd_bon_dets.article_id');
})
->join('fournisseurs', function ($join) {
      $join->on('fournisseurs.id', '=', 'cmd_factures.fournisseur_id');
  })
->Where(function ($query) use($searchFilterText){
    return $query   
          ->Where('fournisseurs.nom','like','%'.$searchFilterText.'%')
          ->orWhere('articles.des','like','%'.$searchFilterText.'%')
          ->orWhere('articles.code_article','like','%'.$searchFilterText.'%')
          ->orWhere('articles.code_a_barre','like','%'.$searchFilterText.'%');
 })

 ->Where(function ($query) use($TTCmin) {
  if( $TTCmin!='' && !($TTCmin===Null)  ){
        return $query
        ->where('cmd_factures.TTTTC','>=',$TTCmin); 
  }
})
->Where(function ($query) use($TTCmax) {
  if( $TTCmax!='' && !($TTCmax===Null)  ){
        return $query
        ->where('cmd_factures.TTTTC','<=',$TTCmax); 
  }
})

->select('cmd_factures.*','fournisseurs.nom')
->orderBy('cmd_factures.created_at', 'desc')
->distinct('cmd_factures.FactureID');
//->get();

    $countQuery = $cmd_factures->count();
    $cmd_factures = $cmd_factures->skip($skipped)->take($itemsPerPage)->get();
    return response()->json(['factures' => $cmd_factures, 'me' => $me, 'total' => $countQuery]);

    //return response()->json($cmd_factures);

}


public function suprimerFacture($id){
 $success=true;
 $BonsIds= cmd_facture_det::where('facture_id','=',$id)->select('bon_id')->get();
 if(!$BonsIds){$success=false;}

 foreach($BonsIds as $BonsId){
   $BonID=$BonsId['bon_id'];
   $cmd_bon=cmd_bon::where('BonID','=',$BonID)->update(['isFactured'=>false]);
   if(!$cmd_bon){$success=false;}
 }

 if($success){
 $facture= cmd_facture::where('FactureID','=',$id)->update(['exist'=>false]);
 if(!$facture){$success=false;}
 }
 return response()->json($success);
}




public function addRetour(Request $request,$id){
  $success=true;
  $msg="Retour avec succées .";

    $UserID=JWTAuth::user()->id;
    $retourdet=$request->retour;
    
    $Logique=$this->TestLogiqueAddRetour($id,$retourdet);

    if($Logique){
    $retour=cmd_retour::create([
      'user_id'=>$UserID,
      'cmd_rec_id'=>$id,
    ]);
    $RetourID=$retour->RetourID;
    if($retour){

      foreach($retourdet as $article){
        $articleObj=(object)$article;
        $retour_det=cmd_retour_det::create([
          'retour_id'=>$RetourID,
          'article_id'=>$articleObj->ArticleID,
          'QteRet'=>$articleObj->QteRet
        ]);
        if(!$retour_det){
          $success=false;
          $msg="Error .";
        }
    
      } 
      
      if($success){
       
        $AddOrDelete1="add";
        $Retour_UpdateStockByRetourID=$this->UpdateStockByRetourID($RetourID,$AddOrDelete1);
        if(!$Retour_UpdateStockByRetourID){$success=false; $msg="Error .";}

        $AddOrDelete2="add";
        $RetourRetourPUMP=$this->RetourPUMP($RetourID,$AddOrDelete2);
        if(!$RetourRetourPUMP){$success=false;}

        }

    }else{$success=false; $msg="Error .";}

  }else{
    $success=false;
    $msg="Pas logique .";
  }
    return response()->json(['success'=>$success,'msg'=>$msg,'RetourID'=>$RetourID]);

}

public function TestLogiqueAddRetour($CmdRecID,$RetourDet){

  $cmd_retours=cmd_retour::where('cmd_rec_id','=',$CmdRecID)->get();
  $TTRetourDet=array();
  $TTRetourDet=$this->TTRetourDetByCmd_retours($cmd_retours);

  foreach($RetourDet as $article){
      $articleObj=(object)$article;
      $ArticleID=$articleObj->ArticleID;
      $QteRet=$articleObj->QteRet;

      $QteTTRetourne=0;
      if(isset($TTRetourDet[$ArticleID])){$QteTTRetourne=$TTRetourDet[$ArticleID];};
      
      $QteTTRecepte=$this->GetTTRecArticleByCmdRecIDAndArticleID($CmdRecID,$ArticleID);
      if($QteTTRecepte!=false){
        $RestePossibleARetourne=$QteTTRecepte-$QteTTRetourne;
        if($QteRet>$RestePossibleARetourne){return false;}
      }else{
        return false;
      }

      $stock=$this->GetStockByArticleID($ArticleID);
      if($stock-$QteRet<0){return false;}

  }

  return true;

}


public function getRetour($id){
 
 $RetourID=$id;

 $cmd_retour=cmd_retour::where('RetourID','=',$RetourID)
 ->join('users', function ($join) {
  $join->on('users.id', '=', 'cmd_retours.user_id');
 })
 ->select('cmd_retours.*','users.name')
 ->first();

 if($cmd_retour){
 $cmd_retour_det=cmd_retour_det::where('retour_id','=',$RetourID)
 ->join('articles', function ($join) {
  $join->on('articles.ArticleID', '=', 'cmd_retour_dets.article_id');
 })
 ->select('cmd_retour_dets.*','articles.des')
 ->get();
 return response()->json(['isDeleted'=>0,'Retour'=>$cmd_retour,'RetourDet'=>$cmd_retour_det]);
 }

 $cmd_corb_retour=cmd_corb_retour::where('RetourID','=',$RetourID)
 ->join('users', function ($join) {
  $join->on('users.id', '=', 'cmd_corb_retours.user_id');
 })
 ->select('cmd_corb_retours.*','users.name')
 ->first();
 
 if($cmd_corb_retour){
 $cmd_corb_retour_det=cmd_corb_retour_det::where('retour_id','=',$RetourID)
 ->join('articles', function ($join) {
  $join->on('articles.ArticleID', '=', 'cmd_corb_retour_dets.article_id');
 })
 ->select('cmd_corb_retour_dets.*','articles.des')
 ->get();
 return response()->json(['isDeleted'=>1,'Retour'=>$cmd_corb_retour,'RetourDet'=>$cmd_corb_retour_det]);
 }

}

public function deleteRetour($id){
  $success=true;
  $RetourID=$id;
 
  $AddOrDelete1="delete";
  $Retour_UpdateStockByRetourID=$this->UpdateStockByRetourID($RetourID,$AddOrDelete1);
  if(!$Retour_UpdateStockByRetourID){$success=false;}
  $AddOrDelete2="delete";
  $RetourRetourPUMP=$this->RetourPUMP($RetourID,$AddOrDelete2);
  if(!$RetourRetourPUMP){$success=false;}
  
  $cmd_retour=cmd_retour::where('RetourID','=',$id)->first();
  $INSERT_cmd_corb_retour=cmd_corb_retour::insert($cmd_retour->toArray());
  if(!$INSERT_cmd_corb_retour){$success=false;}
  $cmd_retour_dets=cmd_retour_det::where('retour_id','=',$id)->get();
  foreach($cmd_retour_dets as $cmd_retour_det){
   $INSET_cmd_corb_retour_det=cmd_corb_retour_det::insert($cmd_retour_det->toArray());
   if(!$INSET_cmd_corb_retour_det){$success=false; }
  }
  
  if($success){
  $cmd_retour_det=cmd_retour_det::where('retour_id','=',$RetourID)->delete();
  $cmd_retour=cmd_retour::where('RetourID','=',$RetourID)->delete();
  if(!$cmd_retour_det || !$cmd_retour){$success=false;}
  }

  return response()->json($success);
}

public function UpdateStockByRetourID($RetourID,$AddOrDelete){
  $success=true;
  $signe=1;
  if($AddOrDelete=="add"){$signe=-1;}
  $cmd_retour_dets=cmd_retour_det::where('retour_id','=',$RetourID)->get();
  if(!$cmd_retour_dets){$success=false;}
  if($cmd_retour_dets){
   foreach($cmd_retour_dets as $article){
     // mouvements
     $ArticleID=$article['article_id'];
     $QteRet=$signe*$article['QteRet'];

     $retour_UpdateStockByArticleID=$this->UpdateStockByArticleID($ArticleID,$QteRet);
     if(!$retour_UpdateStockByArticleID){$success=false;}
   }
  }else{
  $success=false;
  }
  return $success;
}

public function getRetours($corbeille){
  $start = new Carbon('first day of last month'); $start->hour(0)->minute(0)->second(0);
  if($corbeille==1){
    $retours=cmd_corb_retour::whereDate('cmd_corb_retours.created_at','>=',$start)->get();
  }
  else{
    $retours=cmd_retour::whereDate('cmd_retours.created_at','>=',$start)->get();
  }
  return response()->json($retours);
}


public function filterRetours(Request $request,$corbeille){

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

    $UserID = JWTAuth::user()->id;
    $me = app('App\Http\Controllers\Divers\generale')->getUserPosts($UserID);
    $me = $me->original;
    $me = $me[0];
    $posts = $me->posts;

    $isAdmin = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, ['Admin']);
    $isMethode = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, ['Methode']);
    $OthersAuthorized = ['ChefDeEquipe', 'ChefDePoste', 'ResponsableMaintenance'];
    $isOthersAuthorized = app('App\Http\Controllers\Divers\generale')->PostsHavePosts($posts, $OthersAuthorized);


  if($corbeille==0){
    $tab_bons='cmd_retours';
    $tab_bon_det='cmd_retour_dets';
  }else if($corbeille==1){
    $tab_bons='cmd_corb_retours';
    $tab_bon_det='cmd_corb_retour_dets';
  }else{
    return response()->json([]);
  }
  
  /*
    $searchFilterText=$request->input('searchFilterText');
    $datemin=$request->input('datemin');
    $datemax=$request->input('datemax');

    $start = new Carbon('first day of last month'); $start->hour(0)->minute(0)->second(0);
    
    if($datemin!=''){
       $datemin= Carbon::parse($datemin);
       $datemin->hour(0)->minute(0)->second(0);
    }else{$datemin=$start;}
    if($datemax!=''){ 
      $datemax= Carbon::parse($datemax);
      $datemax->hour(0)->minute(0)->second(0);
      $datemax->addDays(1);
    }else{$datemax=Carbon::parse('2120-01-01 00:00:00');}
 */ 


 if($corbeille==1){
 $cmd_receptions=cmd_corb_retour::
 whereDate($tab_bons.'.created_at','>=',$datemin)
 ->whereDate($tab_bons.'.created_at','<=',$datemax)

  ->join($tab_bon_det, function ($join)use($tab_bons,$tab_bon_det){
    $join->on($tab_bon_det.'.retour_id', '=', $tab_bons.'.RetourID');
  })
  ->join('articles', function ($join)use($tab_bon_det){
    $join->on('articles.ArticleID', '=', $tab_bon_det.'.article_id');
  })
  ->join('fournisseurs', function ($join){
        $join->on('fournisseurs.id', '=', 'articles.fournisseur_id');
    })
  ->join('users', function ($join)use($tab_bons){
        $join->on('users.id', '=', $tab_bons.'.user_id');
    })
  ->Where(function ($query) use($searchFilterText){
      return $query   
            ->where('users.name','like','%'.$searchFilterText.'%')
            ->orWhere('fournisseurs.nom','like','%'.$searchFilterText.'%')
            ->orWhere('articles.des','like','%'.$searchFilterText.'%')
            ->orWhere('articles.code_article','like','%'.$searchFilterText.'%')
            ->orWhere('articles.code_a_barre','like','%'.$searchFilterText.'%');
   })
  ->select($tab_bons.'.*','users.name')
  ->orderBy($tab_bons.'.created_at', 'desc')
  ->distinct($tab_bons.'.RetourID');
  
  //->get();

   
  }

  if($corbeille==0){
    $cmd_receptions=cmd_retour::
    whereDate($tab_bons.'.created_at','>=',$datemin)
    ->whereDate($tab_bons.'.created_at','<=',$datemax)
   
     ->join($tab_bon_det, function ($join)use($tab_bons,$tab_bon_det){
       $join->on($tab_bon_det.'.retour_id', '=', $tab_bons.'.RetourID');
     })
     ->join('articles', function ($join)use($tab_bon_det){
       $join->on('articles.ArticleID', '=', $tab_bon_det.'.article_id');
     })
     ->join('fournisseurs', function ($join){
           $join->on('fournisseurs.id', '=', 'articles.fournisseur_id');
       })
     ->join('users', function ($join)use($tab_bons){
           $join->on('users.id', '=', $tab_bons.'.user_id');
       })
     ->Where(function ($query) use($searchFilterText){
         return $query   
               ->where('users.name','like','%'.$searchFilterText.'%')
               ->orWhere('fournisseurs.nom','like','%'.$searchFilterText.'%')
               ->orWhere('articles.des','like','%'.$searchFilterText.'%')
               ->orWhere('articles.code_article','like','%'.$searchFilterText.'%')
               ->orWhere('articles.code_a_barre','like','%'.$searchFilterText.'%');
      })
     ->select($tab_bons.'.*','users.name')
     ->orderBy($tab_bons.'.created_at', 'desc')
     ->distinct($tab_bons.'.RetourID');
     
    // ->get();
     }


     $countQuery = $cmd_receptions->count();
     $cmd_receptions = $cmd_receptions->skip($skipped)->take($itemsPerPage)->get();
     return response()->json(['retours' => $cmd_receptions, 'me' => $me, 'total' => $countQuery]);

     // return response()->json($cmd_receptions);
  
}



public function RetourPUMP($RetourID,$AddOrDelete){
  $success=true;
  $cmd_retour=cmd_retour::where('RetourID','=',$RetourID)->first();
  $CmdRecID=$cmd_retour->cmd_rec_id;

  $cmd_retour_dets=cmd_retour_det::where('retour_id','=',$RetourID)->get();
  if(!$cmd_retour_dets){$success=false;}

  $BonDet=$this->GetBonDetByCmdRecID($CmdRecID);
  if($BonDet){
  foreach($cmd_retour_dets as $article){
    $ArticleID=$article['article_id'];
    $QteRet=$article['QteRet'];

    $TypeID='RetourID';
    $ID=$RetourID;
    if($AddOrDelete=="add"){
      $type='addRetour';
      $Qte=-$QteRet;
    }
    if($AddOrDelete=="delete"){
      $type='deleteRetour';
      $Qte=$QteRet;
    }
   
    $Prix=$this->GetPrixArticleByBonDetAndArticleID($BonDet,$ArticleID);
    $retPUMP=$this->PUMP($ArticleID,$TypeID,$ID,$type,$Qte,$Prix);
    if(!$retPUMP){$success=false;}
  }
}
  return $success;
}


public function GetBonDetByCmdRecID($CmdRecID){
  $cmd_bon=cmd_bon::where('cmd_rec_id','=',$CmdRecID)->first();
  if(!$cmd_bon){return false;}
  $BonID=$cmd_bon->BonID;
  $cmd_bon_dets=cmd_bon_det::where('bon_id','=',$BonID)->get();
  if(!$cmd_bon_dets){return false;}
  return $cmd_bon_dets;
}

public function GetPrixArticleByBonDetAndArticleID($BonDet,$ArticleID){
 foreach($BonDet as $article){
  $article_id=$article['article_id'];
  if($article_id == $ArticleID){
    return $article['PrixHT'];
  }
 }
 return false;
}
  //################################################################
  //################################################################
  //################################################################

  public function BonIdsToFacture($BonsIds,$FactureID,$CreateOrShow){

    $TTArticles=Array();
    $TTTTC=0;
    foreach($BonsIds as $BonId){

      if($CreateOrShow=='create'){
      $facture_det=cmd_facture_det::create(['facture_id'=>$FactureID,'bon_id'=>$BonId]);
      $isFactured=cmd_bon::where('BonID','=',$BonId)->update(['isFactured'=>true]);
      }

      $articles=cmd_bon_det::where('bon_id','=',$BonId)
      ->join('articles', function ($join) {
        $join->on('articles.ArticleID', '=', 'cmd_bon_dets.article_id');
      })
      ->select('articles.ArticleID','articles.des','articles.artTVA','cmd_bon_dets.QteBon','cmd_bon_dets.PrixHT',)
      ->get();
  
  
    foreach($articles as $article){

      if( isset($TTArticles[$article->ArticleID]) ){

        $value=(object)$TTArticles[$article->ArticleID];
        $value->TTQteBon+=$article->QteBon;

        $QtePrix=new \stdClass();
        $QtePrix->QteBon=$article->QteBon;
        $QtePrix->PrixHT=$article->PrixHT;
        
        array_push($value->ArrayQtePrix,$QtePrix);
       
        //$value->artTVA=$article->artTVA;
        $TTArticles[$article->ArticleID]=$value;

      }
      else{
        $Type=new \stdClass();
        $Type->ArticleID=$article->ArticleID;
        $Type->des=$article->des;
        $Type->TTQteBon=$article->QteBon;
        $Type->artTVA=$article->artTVA;

        $QtePrix=new \stdClass();
        $QtePrix->QteBon=$article->QteBon;
        $QtePrix->PrixHT=$article->PrixHT;

        $Type->ArrayQtePrix=array($QtePrix);
        $TTArticles[$article->ArticleID]=$Type;
      }

    }

  }


  foreach($TTArticles as $TTArticle){
    $ArrayQtePrix = $TTArticle->ArrayQtePrix;
    foreach($ArrayQtePrix as $QtePrix){
    $TT=$QtePrix->QteBon * $QtePrix->PrixHT;
    $TTTTC+=$TT;
    }
  }

  return ['TTArticles'=>$TTArticles,'TTTTC'=>$TTTTC];

  }

  public function MathRound($nb){ return ( round($nb * 1000000)/1000000); }
  public function MathRound4($nb){ return ( round($nb * 1000)/1000); }

  public function ExistCmd($CommandeID){
    $res=cmd::where('CommandeID','=',$CommandeID)->select('exist')->first();
    $exist=$res->exist;
    return $exist;
  }

  public function statutDaToCommande($daarticle_id){
    $nvstatut='confirmeCommande';
    $res=daarticle::where('id','=',$daarticle_id)->first();
    $da_id=$res->da_id;
    $res=da::where('id','=',$da_id)->first();
    $statut=$res->statut;
  if($statut!='confirmeCommande' && $statut!= 'confirmeParAdminCommande' ){
    if($statut=='confirmeParAdmin'){$nvstatut='confirmeParAdminCommande';}
    $resUpdate=da::where('id','=',$da_id)->update(['statut'=>$nvstatut]);
      }
  }


  public function CmdDasIds($CommandeID){
    $da_ids=cmd_daarticle::where('commande_id','=',$CommandeID)
    ->join('daarticles', function ($join) {
      $join->on('daarticles.id', '=', 'cmd_daarticles.daarticle_id');
    })
    ->join('das', function ($join) {
      $join->on('das.id', '=', 'daarticles.da_id');
    })
    ->select('das.id')
    ->get();
    return $da_ids;
  }

  public function DaArticlesDeDaEnCmd($DaID){
     $daarticles_ids_cmde=daarticle::where('da_id','=',$DaID)
     ->join('cmd_daarticles', function ($join) {
      $join->on('cmd_daarticles.daarticle_id', '=', 'daarticles.id');
     })
     ->join('cmds', function ($join) {
      $join->on('cmds.CommandeID', '=', 'cmd_daarticles.commande_id')
      ->where('cmds.exist','=',1);
     })
     ->select('daarticles.id')
     ->get();
     return $daarticles_ids_cmde;
  }


  public function DaStatutToConfirme($DaID){
    $nvstatut='confirme';
    $ResAncienStatut=da::where('id','=',$DaID)->first();
    $AncienStatut=$ResAncienStatut->statut;
    if($AncienStatut=='confirmeParAdminCommande' || $AncienStatut=='confirmeParAdminFerme' ){ $nvstatut='confirmeParAdmin'; }
    $resUpdate=da::where('id','=',$DaID)->update(['statut'=>$nvstatut]);
  }



public function ChangementsNecessaireApresSupressionCmd($CommandeID){
  $das_ids=$this->CmdDasIds($CommandeID);
    foreach($das_ids as $da_id){
       $DaID=$da_id->id;
       $daarticlesencmd = $this->DaArticlesDeDaEnCmd($DaID);
       if( count($daarticlesencmd)==0 ){ 
        $this->DaStatutToConfirme($DaID) ;
      }
    }
}

public function DaArtcileDeCmdEnCmd($CommandeID){
  $ttarticles_ids_cmde=Array();
  $daarticles_ids=cmd_daarticle::where('commande_id','=',$CommandeID)->select('daarticle_id')->get();

     foreach($daarticles_ids as $daarticles_id){
     $DaArticleID=$daarticles_id->daarticle_id;
     $daarticles_ids_cmde=cmd_daarticle::where('daarticle_id','=',$DaArticleID)
     ->join('cmds', function ($join) {
      $join->on('cmds.CommandeID', '=', 'cmd_daarticles.commande_id')
      ->where('cmds.exist','=',1);
     })
     ->select('cmd_daarticles.CmdDaArticleID')
     ->get();
     if(count($daarticles_ids_cmde)>0){array_push($ttarticles_ids_cmde,$daarticles_ids_cmde);}
     
    }
  return $ttarticles_ids_cmde;
}


public function ChangementsNecessaireApresRestaurerCmd($CommandeID){
  $das_ids=$this->CmdDasIds($CommandeID);
  foreach($das_ids as $da_id){
     $DaID=$da_id->id;
      $this->DaStatutToCommande($DaID) ;
  }
}


public function DaStatutToCommande($DaID){
  $nvstatut='confirmeCommande';
    $ResAncienStatut=da::where('id','=',$DaID)->first();
    $AncienStatut=$ResAncienStatut->statut;
    if($AncienStatut=='confirmeParAdmin' ){ $nvstatut='confirmeParAdminCommande'; }
    $resUpdate=da::where('id','=',$DaID)->update(['statut'=>$nvstatut]);
}


public function StatutDaToCommandeByDaID($DaID){
    $nvstatut='confirmeCommande';
    $res=da::where('id','=',$DaID)->first();
    $statut=$res->statut;
  if($statut == 'confirmeFerme' || $statut == 'confirmeParAdminFerme' ){
    if($statut=='confirmeParAdminFerme'){$nvstatut='confirmeParAdminCommande';}
    $resUpdate=da::where('id','=',$DaID)->update(['statut'=>$nvstatut]);
  }
}


public function ChangementsNecessaireApresFermetureCmd($CommandeID){
 
  $das_ids=$this->CmdDasIds($CommandeID);

    foreach($das_ids as $da_id){
       $ferme=true;
       $DaID=$da_id->id;

       // les articles non cmde 
       $count=count($this->DaArticlesDeDaNnCmd($DaID));
       if($count>0){ $ferme=false; }

       $daarticle_cmde=$this->DaArticlesDeDaEnCmd($DaID);
       foreach($daarticle_cmde as $daarticle){
         $id=$daarticle->id;
         $statut = $this->StatutDeCmdByDaArticle($id);
         if($statut!='ferme' && $statut!='vide' ){$ferme=false;}
       }
       
       if($ferme){
        $nvstatut='confirmeFerme'; 
        $ResAncienStatut=da::where('id','=',$DaID)->first();
        $AncienStatut=$ResAncienStatut->statut;
          if($AncienStatut=='confirmeParAdminCommande' || $AncienStatut=='confirmeCommande' ){
             if($AncienStatut=='confirmeParAdminCommande'){ $nvstatut='confirmeParAdminFerme'; }
             $resUpdate=da::where('id','=',$DaID)->update(['statut'=>$nvstatut]);
          }
        }


       }

    }


public function DaArticlesDeDaNnCmd($DaID){
  $daarticles_ids_nn_cmde=daarticle::where('da_id','=',$DaID)
  ->whereNotExists( function ($query) {
    $query->select(DB::raw(1))
    ->from('cmd_daarticles')
    ->join('cmds', function ($join) {
        $join->on('cmds.CommandeID', '=', 'cmd_daarticles.commande_id')
        ->where('cmds.exist','=',1);
    })
    ->whereRaw('cmd_daarticles.daarticle_id = daarticles.id');
  })
  ->select('daarticles.id')
  ->get();
  return $daarticles_ids_nn_cmde;

}



public function StatutDeCmdByDaArticle($daarticle_id){
  $res=cmd_daarticle::where('daarticle_id','=',$daarticle_id)
  ->join('cmds', function ($join) {
    $join->on('cmds.CommandeID', '=', 'cmd_daarticles.commande_id')
    ->where('cmds.exist','=',1);
   })
  ->select('cmds.statut')
  ->first();
  $statut=$res->statut;
  return $statut;
}




}
