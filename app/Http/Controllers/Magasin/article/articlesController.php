<?php

namespace App\Http\Controllers\Magasin\article;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\User;

use Illuminate\Support\Facades\DB;


use App\Model\Atelier\atel_article;
use App\Model\Magasin\article;
use App\Model\Magasin\art_cara;
use App\Model\Magasin\art_fam_cara;
use App\Model\Magasin\art_fam_cara_det;
use App\Model\Magasin\art_article_cara;
use App\Model\Magasin\art_pp;

use App\Model\Magasin\art_outil;
use App\Model\Magasin\art_etat;
use App\Model\Magasin\art_secteur;
use App\Model\Magasin\art_utilisation;
use App\Model\Magasin\art_outil_utilisation;

use App\Model\Magasin\art_type;
use App\Model\Magasin\art_use;
use App\Model\Magasin\art_corb_use;

use App\Model\Magasin\art_inventaire;
use App\Model\Magasin\art_inventaire_det;


use App\Model\Magasin\art_famille;
use App\Model\Divers\unite;
use App\Model\Magasin\art_emplacement;
use App\Model\Divers\file;
use App\Model\Divers\dossier;

use App\Model\Divers\intervenant;
use App\Model\Divers\les_unite;
use App\Model\Divers\ligne;
use App\Model\Divers\equipement;
use App\Model\Divers\sous_equipement;


use App\Model\Divers\error;



use App\Model\Achat\daarticle;
use App\Model\Achat\fournisseur;
use phpDocumentor\Reflection\Types\Boolean;

use Carbon\Carbon;
use Illuminate\Support\Facades\Date;
use JWTFactory;
use JWTAuth;

class articlesController extends Controller
{
  public function index()
  {
    return article::all();
  }

  public function indexsearch(Request $request)
  {
    $search = $request->input('search');
    return article::where('des', 'Like', '%' . $search . '%')
      ->join('unites', function ($join) {
        $join->on('unites.UniteID', '=', 'articles.unite_id');
      })
      ->select('articles.*', 'unites.unite')
      ->get();
  }

  public function getArticleAtelierListSearch(Request $request){
    $search = $request->input('search');
    return atel_article::where('des', 'Like', '%' . $search . '%')
      ->select('atel_articles.*')
      ->get();
  }


  public function getlistesousfamilles(Request $request,$id){
     $ret=art_fam_cara::where('famille_id','=',$id)->get();
     return response()->json($ret);
  }

  public function getfamilles(Request $request){
    return response()->json(art_famille::where('FamilleID','<>',0)->get());
  }

  public function getunites(Request $request){
    return response()->json(unite::all());
  }

  public function getemplacements(Request $request){
    return response()->json(art_emplacement::all());
  }




  public function getform(Request $request, $id)
  {
    $success = true; // na9is taghlif ama misélich 
    $art_fam_cara_dets = art_fam_cara_det::where('fam_cara_id', '=', $id)->get();
    $array_fields = array();

    if ($art_fam_cara_dets) {
      foreach ($art_fam_cara_dets as $art_fam_cara_det) {

        $FamCaraDetID = $art_fam_cara_det->FamCaraDetID;
        $field = new \stdClass();
        $field->type = $art_fam_cara_det->input;
        $field->label = $art_fam_cara_det->label;
        $field->name = $art_fam_cara_det->name;

        $arr_validators = array();

        if ($field->type == 'input') {

          $field->inputType = $art_fam_cara_det->type;

          $number = $art_fam_cara_det->number;
          if ($number) {
            $validator = new \stdClass();
            $validator->name = 'required';
            $validator->validator = 'number';
            //$validator->message = $field->name . ' Que des nombres';
            $validator->message ='Que des nombres';
            array_push($arr_validators, $validator);
          }

          $required = $art_fam_cara_det->required;
          if ($required) {
            $validator = new \stdClass();
            $validator->name = 'required';
            $validator->validator = 'Validators.required';
            //$validator->validator='number';
            //$validator->message = $field->name . ' Required';
            $validator->message ='Obligatoire';
            array_push($arr_validators, $validator);
          }
        }

        if ($field->type == 'select') {
          // systeme da55il forcement valeur par default fil addform 
          $default = art_cara::where('fam_cara_det_id', '=', $FamCaraDetID)->where('default', '=', 1)->select('value')->first();
          $field->value = $default->value;

          $arr_options = array();
          $options = art_cara::where('fam_cara_det_id', '=', $FamCaraDetID)->select('value')->get();

          foreach ($options as $option) {
            array_push($arr_options, $option->value);
          }
          $field->options = $arr_options;

          $required = $art_fam_cara_det->required;
          if ($required) {
            $validator = new \stdClass();
            $validator->name = 'required';
            $validator->validator = 'Validators.required';
            //$validator->message = $field->name . ' Required';
            $validator->message ='Obligatoire';
            array_push($arr_validators, $validator);
          }
        }


        if ($field->type == 'radiobutton') {
          $default = art_cara::where('fam_cara_det_id', '=', $FamCaraDetID)->where('default', '=', 1)->select('value')->first();
          if ($default) {
            $field->value = $default->value;
          } else {
            // first valuer houwa elli par default 
            $default = art_cara::where('fam_cara_det_id', '=', $FamCaraDetID)->select('value')->first();
            if ($default) {
              $field->value = $default->value;
            }
          }

          $arr_options = array();
          $options = art_cara::where('fam_cara_det_id', '=', $FamCaraDetID)->select('value')->get();
          foreach ($options as $option) {
            array_push($arr_options, $option->value);
          }
          $field->options = $arr_options;
        }

        if ($field->type == 'checkbox') {
         // $default = art_cara::where('fam_cara_det_id', '=', $FamCaraDetID)->where('default', '=', 1)->select('value')->first();
          $default = art_cara::where('fam_cara_det_id', '=', $FamCaraDetID)->select('default')->first();
          if ($default) {
            $field->value = $default->default;
           // $field->value = $default->value;
          }
        }
        $field->validations = $arr_validators;
        array_push($array_fields, $field);
      }
    } else {
      $success = false;
    }

    if ($success) {
      return response()->json($array_fields);
    } else {
      return response()->json($success);
    }
  }



  public function setform(Request $request)
  {
    $success = true;
    $msg="Sous famille est ajouté avec succées .";
    $name = $request->input('name');
    $FamilleID = $request->input('famille_id');
    $fields = $request->input('fields');
    // test de validation de formulaire
    // if false return false
    $hasCara=false; if(count($fields)>0){$hasCara=true;}
    $art_fam_cara = art_fam_cara::create(['famille_id'=>$FamilleID,'name_famille' => $name,'hasCara'=>$hasCara]);


    if ($art_fam_cara) {

      $FamCaraID = $art_fam_cara->FamCaraID;

      foreach ($fields as $field) {
        $field = (object) $field;
        $name = $field->name;
        $label = $field->label;
        $ypeField = $field->typeField;

        if ($ypeField == 'text') {
          $type = $field->type;
          $required = $field->required;
          $typeBoolean = false;
          if ($type == 'number') {
            $typeBoolean = true;
          }
          $required = $field->required;
          $art_fam_cara_det = art_fam_cara_det::create([
            'fam_cara_id' => $FamCaraID,
            'name' => $name,
            'label' => $label,
            'required' => $required,
            'type' => $ypeField,
            'input' => 'input',
            'number' => $typeBoolean
          ]);
          if (!$art_fam_cara_det) {
            $success = false;
          }
        }


        if ($ypeField == 'select' || $ypeField == 'radiobutton' || $ypeField == 'checkbox') {
          $required = $field->required;
          $art_fam_cara_det = art_fam_cara_det::create([
            'fam_cara_id' => $FamCaraID,
            'name' => $name,
            'label' => $label,
            'required' => $required,
            'type' => '',
            'input' => $ypeField,
            'number' => false
          ]);
          if ($art_fam_cara_det) {
            $FamCaraDetID = $art_fam_cara_det->FamCaraDetID;
            $options = $field->phones;

            if ($ypeField == 'select') {
              $vide = $field->vide;
              $haveDefault = false;

              foreach ($options as $option) {
                $option = (object) $option;
                if ($option->default == true) {
                  $haveDefault = true;
                }
              }

              if ($vide) {
                $ChoisirOption = new \stdClass();
                $ChoisirOption->value = '';

                if ($haveDefault) {
                  $ChoisirOption->default = false;
                } else {
                  $ChoisirOption->default = true;
                }

                // lahné mazélét faza barka bil internet 
                array_push($options, $ChoisirOption);
                // elli hiya da55il melloul méch melle55er

              } else {
                if ($haveDefault == false) {
                  $options[0]['default'] = true;
                }
              }
            }

            foreach ($options as $option) {
              $option = (object) $option;
              $defaultBoolean = false;
              $art_cara = art_cara::create([
                'fam_cara_det_id' => $FamCaraDetID,
                'value' => $option->value,
                'default' => $option->default
              ]);
              if (!$art_cara) {
                $success = false;
              }
            }
          } else {
            $success = false;
          }
        }
      }
    } else {
      $success = false;
      $msg="Error .";
    }
    return response()->json(['success'=>$success,'msg'=>$msg,'FamCaraID'=>$art_fam_cara->FamCaraID]);
  }



  public function addarticle(Request $request)
  {
    
    $success = true;
    $msg="Article est ajoutée avec succées .";

    $article_form = $request->input('article_form');
    unset($article_form['created_at']);
    unset($article_form['updated_at']);
    
    $cara_form = $request->input('cara_form');

    $retourAddArticle = article::create($article_form);  // Insertion d'article dans tableau articles
    
    // Mouvement du stock // amelioration
    // Insertion dans tableau mouvements

    if ($retourAddArticle) {
      $ArticleID = $retourAddArticle->ArticleID;
/*
      $An=article::where('ArticleID','=',$ArticleID)->select('articles.stock','articles.PrixHT')->first();
      $AnPUMP=$An->PrixHT;
      $Stock=$An->stock;
*/
 
      $art_pump=art_pp::create(['article_id'=>$ArticleID,'TypeID'=>'','ID'=>0,'type'=>'addArticle',
      'NvQte'=>$article_form['stock'],'NvPrixHT'=>$article_form['PrixHT'],'AnPUMP'=>0,'NvPUMP'=>$article_form['PrixHT'],'exist'=>1]);
      
      
      $FamCaraID = $article_form['sous_famille_id'];
      if ($FamCaraID != '0') {
        $createOrUpdate = 'create';

        error::insert(['error'=>'1 = AddOrUpdateCaraArticle(FamCaraID:'.$FamCaraID.'['.gettype($FamCaraID).'], +cara_form:$cara_form,ArticleID:'.$ArticleID.',createOrUpdate:'.$createOrUpdate.')']);
        error::insert(['error'=>"2 = ".json_encode($cara_form)]);

        $AddOrUpdateCaraArticle = $this->AddOrUpdateCaraArticle($FamCaraID, $cara_form, $ArticleID, $createOrUpdate);
        if (!$AddOrUpdateCaraArticle) {
          $success = false;
          $msg="Error . ";
        }
      }
    } else {
      $success = false;
      $msg="Error .";
    }
    return response()->json(['success'=>$success,'msg'=>$msg,'ArticleID'=>$ArticleID]);
  }




  public function getarticle(Request $request, $id)
  {
    $success = true;
    $source = $request->input('source'); // modification // affichage 
    //$FamCaraID = 8; // get FamCaraID from articles where ArticleID=$id
    $RetourFamCaraID = article::where('ArticleID','=',$id)->first();
    $FamCaraID=$RetourFamCaraID->sous_famille_id;
    $formArticle = $RetourFamCaraID;

    if ($RetourFamCaraID) {
      $arr_fields = array();
      if ($FamCaraID != 0) {

        $art_fam_cara_dets = art_fam_cara_det::where('fam_cara_id', '=', $FamCaraID)->get();

 //          error::insert(['error'=>json_encode($art_fam_cara_dets)]);
/*
            [{"FamCaraDetID":78,"fam_cara_id":36,"name":"moteurtext","label":"Nom De Moteur","required":1,"type":"text","input":"input","number":0,"created_at":"2020-03-25 15:14:58","updated_at":"2020-03-25 15:14:58"}
            ,{"FamCaraDetID":79,"fam_cara_id":36,"name":"numerotext","label":"Numero De moteur","required":0,"type":"text","input":"input","number":1,"created_at":"2020-03-25 15:14:58","updated_at":"2020-03-25 15:14:58"}
            ,{"FamCaraDetID":80,"fam_cara_id":36,"name":"courant","label":"Courant","required":1,"type":"","input":"select","number":0,"created_at":"2020-03-25 15:14:58","updated_at":"2020-03-25 15:14:58"},
            {"FamCaraDetID":81,"fam_cara_id":36,"name":"puissance","label":"Puissance","required":0,"type":"","input":"select","number":0,"created_at":"2020-03-25 15:14:59","updated_at":"2020-03-25 15:14:59"},
            {"FamCaraDetID":82,"fam_cara_id":36,"name":"type","label":"Type","required":0,"type":"","input":"radiobutton","number":0,"created_at":"2020-03-25 15:14:59","updated_at":"2020-03-25 15:14:59"},
            {"FamCaraDetID":83,"fam_cara_id":36,"name":"trueorfalse","label":"True Or Famse","required":0,"type":"","input":"checkbox","number":0,"created_at":"2020-03-25 15:14:59","updated_at":"2020-03-25 15:14:59"}]
*/

        if ($art_fam_cara_dets) {
          foreach ($art_fam_cara_dets as $art_fam_cara_det) {

            $FamCaraDetID = $art_fam_cara_det->FamCaraDetID;
            
            if ($source == 'modification') {
              $labelorname = $art_fam_cara_det->name;
            }
            if ($source == 'affichage') {
              $labelorname = $art_fam_cara_det->label;
              $field_name=$art_fam_cara_det->name;
              $field_input=$art_fam_cara_det->input;
              $field_number=$art_fam_cara_det->number;
            }

            $art_article_cara = art_article_cara::where('article_id', '=', $id)
              ->where('fam_cara_det_id', '=', $FamCaraDetID)->first();

           // return response()->json($art_article_cara);
           // return response()->json($FamCaraDetID.','.$id);

            if ($art_article_cara) {
              $value = '';
              $cara_id = $art_article_cara['cara_id'];
              $isText = $art_article_cara['isText'];
              $text = $art_article_cara['text'];
              $isCheckbox = $art_article_cara['isCheckbox'];
              $checked = $art_article_cara['checked'];

              if ($isText) {
                $value = $text;
              } else if ($isCheckbox) {
                $value = $checked;
              } else {
                $art_cara = art_cara::where('CaraID', '=', $cara_id)->select('value')->first();
                $value = $art_cara->value;
                if (!$art_cara) {
                  $success = false;
                  //return response()->json('t2');
                  //return response()->json($success);
                }
              }

              $field = new \stdClass();
              if ($source == 'modification') {
                $field->name = $labelorname;
              }
              if ($source == 'affichage') {
                $field->label = $labelorname;
                $field->input = $field_input;
                $field->number = $field_number;
                $field->name = $field_name;
              }
              $field->value = $value;
              array_push($arr_fields, $field);
            } else {
              $success = false;
            //  return response()->json($FamCaraDetID.','.$id);
              //return response()->json($FamCaraDetID);
              //return response()->json($success);
            }
          }
        } else {
          //return response()->json('t4');
          $success = false;
        }
      }

      $RetourCara = 'rien';
      if ($source == 'modification') {
        $retourObject = new \stdClass();
        foreach ($arr_fields as $field) {
          $fieldname = $field->name;
          $retourObject->$fieldname = $field->value;
        }
        $RetourCara = $retourObject;
      }
      if ($source == 'affichage') {
        $RetourCara = $arr_fields;
      }
    } else {
      //return response()->json('t1');
      $success = false;
    }

    if ($success == true) {
      return response()->json(['article' => $formArticle, 'cara' => $RetourCara]);
    } else {
      return response()->json($success);
    }
  }



  public function editarticle(Request $request, $id)
  {
    $success = true;
    $msg="Article est modifiée avec succées .";

    $article_form = $request->input('article_form');

    unset($article_form['stock']);
    unset($article_form['ArticleID']);
    unset($article_form['PrixHT']);
    unset($article_form['exist']);
    unset($article_form['created_at']);
    unset($article_form['updated_at']);

    //return response()->json($article_form);
    
    $cara_form = $request->input('cara_form');
    $ArticleID =$id;
    $AncienFamaCaraIDRetour=article::where('ArticleID','=',$ArticleID)->first();
    $AncienFamaCaraID = $AncienFamaCaraIDRetour->sous_famille_id ;
    $retourEditArticle=article::where('ArticleID','=',$ArticleID)->update($article_form);

   // if ($retourEditArticle) {

      $FamCaraID = $article_form['sous_famille_id'];

      $isHouwaBidou = true;
      if ($FamCaraID != $AncienFamaCaraID) {
        $isHouwaBidou = false;
      }

      if ($FamCaraID != '0') {


        if ($isHouwaBidou) {
          $createOrUpdate = 'update';
          $AddOrUpdateCaraArticle = $this->AddOrUpdateCaraArticle($FamCaraID, $cara_form, $ArticleID, $createOrUpdate);

        } else {
  
          $supression = $this->SupressionAncienFamCara($ArticleID);
         // if (!$supression) { $success = false; }

          //if($supression){
          $createOrUpdate = 'create';
          $AddOrUpdateCaraArticle = $this->AddOrUpdateCaraArticle($FamCaraID, $cara_form, $ArticleID, $createOrUpdate);
          //}

        }

        if (!$AddOrUpdateCaraArticle) {
          $success = false;
          $msg="Error";
        }else{
          article::where('ArticleID','=',$ArticleID)->update($article_form);
        }

      }
   // } else {
   //   $success = false;
   // }
    return response()->json(['success'=>$success,'msg'=>$msg]);
  }



  public function SupressionAncienFamCara($ArticleID)
  {
    $supression=art_article_cara::where('article_id', '=', $ArticleID)->delete();
    return $supression;
  }


  public function AddOrUpdateCaraArticle($FamCaraID, $cara_form, $ArticleID, $createOrUpdate)
  {

    
    //error::insert(['error'=>json_encode($cara_form)]);

    $success = true;
    $art_fam_cara_dets = art_fam_cara_det::where('fam_cara_id', '=', $FamCaraID)->get();
    
    $i=1;
    if ($art_fam_cara_dets) {
      foreach ($art_fam_cara_dets as $art_fam_cara_det) {
        $i=$i+1;
        $FamCaraDetID = $art_fam_cara_det->FamCaraDetID;
        
        $field = new \stdClass();
        $field->type = $art_fam_cara_det->input;
        $field->name = $art_fam_cara_det->name;

        $text = '';
        $isText = false;
        $checked = false;
        $isCheckbox = false;
        //if($i>2){return $field;}
        
        //error::insert(['error'=>$field->type.','.$checked]);

        if ($field->type == 'input') {
          $CaraID = 0;
          $text = $cara_form[$field->name];
          $isText = true;
        } else if ($field->type == 'checkbox') {
          $CaraID = 0;
          $checked = $cara_form[$field->name];
          if($checked==null){$checked=false;}
          $isCheckbox = true;

        //  error::insert(['error'=>'Into checkbox [ checked='.$checked.']']);

        } else {
          $getCaraIDByValue = art_cara::where('fam_cara_det_id', '=', $FamCaraDetID)
            ->where('value', '=', $cara_form[$field->name])->select('CaraID')->first();
          $CaraID = $getCaraIDByValue->CaraID;
          if (!$getCaraIDByValue) {
            $success = false;
            return $success;
          }
        }
        
        
        error::insert(['error'=>'3 = i:'.$i.'['.
              '(article_id=>'.$ArticleID.
              ')(fam_cara_det_id=>'.$FamCaraDetID.
              ')(cara_id=>'.$CaraID.
              ')(isText=>'.$isText.
              ')(text=>'.$text.
              ')(isCheckbox=>'.$isCheckbox.
              ')(checked=>'.$checked.
              ')]'.
              ' --- success='.$success.' | createOrUpdate='.$createOrUpdate
              ]);
        
        
      


        if ($success == true) {
          if ($createOrUpdate == 'create') {
            $art_article_cara = art_article_cara::create([
              'article_id' => $ArticleID,
              'fam_cara_det_id' => $FamCaraDetID,
              'cara_id' => $CaraID,
              'isText' => $isText,
              'text' => $text,
              'isCheckbox' => $isCheckbox,
              'checked' => $checked
            ]);
          }
          //return $FamCaraDetID; 

          if ($createOrUpdate == 'update') {
            $art_article_cara = art_article_cara::where('article_id', '=', $ArticleID)
              ->where('fam_cara_det_id', '=', $FamCaraDetID)
              ->update([
                'cara_id' => $CaraID,
                'isText' => $isText,
                'text' => $text,
                'isCheckbox' => $isCheckbox,
                'checked' => $checked
              ]);

            
          }

          // if (!$art_article_cara) {
          //  $success = false;
          //  return $success;
          //}
        }
      }
    } else {
      $success = false;
    }

    return $success;
  }


  public function getfiles($id){
    $dossier=dossier::find($id);
    $DossierID=$dossier->DossierID;
    $files=file::where('dossier_id','=',$DossierID)->get();
    return response()->json($files);
  }

  /*
  public function getarticles(){
    
    $articles = article::where('articles.exist','=',true)
    ->join('unites', function ($join) {
      $join->on('unites.UniteID', '=', 'articles.unite_id');
    })
    ->join('fournisseurs', function ($join) {
      $join->on('fournisseurs.id', '=', 'articles.fournisseur_id');
    })
    ->join('art_emplacements', function ($join) {
      $join->on('art_emplacements.EmplacementID', '=', 'articles.emplacement_id');
    })
    ->get();
    return response()->json($articles);
  }
  */


  public function getArticlesPerPage(Request $request){

    $perPage=$request->input('nb');
    $p=$request->input('p');

    $start = ($p - 1) * $perPage;
    $end = $start + $perPage;
    
    $articles = article::where('articles.exist','=',true)
    ->join('unites', function ($join) {
      $join->on('unites.UniteID', '=', 'articles.unite_id');
    })
    ->join('fournisseurs', function ($join) {
      $join->on('fournisseurs.id', '=', 'articles.fournisseur_id');
    })
    ->join('art_emplacements', function ($join) {
      $join->on('art_emplacements.EmplacementID', '=', 'articles.emplacement_id');
    })
  ->distinct('articles.ArticleID')
  ->orderBy('articles.des', 'asc')
  ->skip($start)->take($perPage)
  ->get();

  $Countarticles = article::all();
  $total=count($Countarticles);
  $test='PerPage:'.$perPage.' , start:'.$start ;
  return response()->json(['items'=>$articles,'total'=>$total,'test'=>$test]);
  
  }



  public function isArticleEnCmd($ArticleID):bool{
    $daarticles_ids_cmde=daarticle::where('article_id','=',$ArticleID)
    ->join('cmd_daarticles', function ($join) {
     $join->on('cmd_daarticles.daarticle_id', '=', 'daarticles.id');
    })
    ->join('cmds', function ($join) {
     $join->on('cmds.CommandeID', '=', 'cmd_daarticles.commande_id')
     ->where('cmds.exist','=',1)
     ->where('cmds.statut','=','ouvert');
    })
    ->select('daarticles.article_id')
    ->get();
    /*
    if(count($daarticles_ids_cmde)>0){
    return true;
    }else{
      return false;   
    }
    */
    //return $daarticles_ids_cmde;
    //$daarticles_ids_cmde=(array)$daarticles_ids_cmde;
    return ( count($daarticles_ids_cmde) > 0 ) ;

   
    //count($daarticles_ids_cmde)>0;
 }


  
  public function getarticles(Request $request,$exist){
    
   
    //$dynamicfilter=$request->input('dynamicfilter');
    //$staticfilter=$request->input('staticfilter');// $request->input('searchFilterText');

    $page = $request->input('page');
    $itemsPerPage = $request->input('itemsPerPage');
    //$nodes = $request->input('nodes');
    
    $skipped = ($page - 1) * $itemsPerPage;
    $endItem = $skipped + $itemsPerPage;

    $UserID = JWTAuth::user()->id;
    $me = app('App\Http\Controllers\Divers\generale')->getUserPosts($UserID);
    $me = $me->original;
    $me = $me[0];
    $posts = $me->posts;
    
    $dynamicfilter=$request->input('dynamicfilter');//$filters['dynamicfilter'];
    $staticfilter=$request->input('staticfilter');
    
    $searchFilterText=$staticfilter['searchFilterText'];
    $famille_id=$staticfilter['famille_id'];
    $sous_famille_id=$staticfilter['sous_famille_id'];
    $emplacement_id=$staticfilter['emplacement_id'];
    $stock_inf=$staticfilter['stock_inf'];
    $stock_sup=$staticfilter['stock_sup'];
    $infalert=$staticfilter['infalert'];
    $infmini=$staticfilter['infmini'];
    $supmaxi=$staticfilter['supmaxi'];
    $encmd=$staticfilter['encmd'];

 $articles=article::
 where('articles.exist','=',$exist)
  ->join('fournisseurs', function ($join) {
        $join->on('fournisseurs.id', '=', 'articles.fournisseur_id');
    })
   
  
  ->join('art_emplacements', function ($join) {
      $join->on('art_emplacements.EmplacementID', '=', 'articles.emplacement_id');
    })
  ->join('art_familles', function ($join) {
      $join->on('art_familles.FamilleID', '=', 'articles.famille_id');
    })
  ->join('art_fam_caras', function ($join) {
      $join->on('art_fam_caras.FamCaraID', '=', 'articles.sous_famille_id');
    })

 ->Where(function ($query) use($searchFilterText){
      return $query   
            ->where('fournisseurs.nom','like','%'.$searchFilterText.'%')
            ->orWhere('articles.des','like','%'.$searchFilterText.'%')
            ->orWhere('articles.code_article','like','%'.$searchFilterText.'%')
            ->orWhere('articles.code_a_barre','like','%'.$searchFilterText.'%')
            ->orWhere('art_emplacements.emplacement','like','%'.$searchFilterText.'%');
   })
  
  
 ->Where(function ($query) use($famille_id) {
    if( $famille_id>0  ){
          return $query
          ->where('articles.famille_id','=',$famille_id); 
    }
  })
 ->Where(function ($query) use($sous_famille_id) {
    if( $sous_famille_id>0  ){
          return $query
          ->where('articles.sous_famille_id','=',$sous_famille_id); 
    }
  })
  ->Where(function ($query) use($emplacement_id) {
    if( $emplacement_id>0  ){
          return $query
          ->where('articles.emplacement_id','=',$emplacement_id); 
    }
  })
  ->Where(function ($query) use($stock_inf) {
    if( $stock_inf!='' && !($stock_inf===Null)  ){
          return $query
          ->where('articles.stock','<=',$stock_inf); 
    }
  })
  ->Where(function ($query) use($stock_sup) {
    if( $stock_sup!='' && !($stock_sup===Null)  ){
          return $query
          ->where('articles.stock','>=',$stock_sup); 
    }
  })
  
  ->Where(function ($query) use($infalert) {
    if( !($infalert===Null) ){
          if($infalert==true){
            return $query
            ->whereRaw('articles.stock < articles.stock_alert'); 
          }
    }
  })

  ->Where(function ($query) use($infmini) {
    if( !($infmini===Null) ){
          if($infmini==true){
            return $query
            ->whereRaw('articles.stock < articles.stock_min'); 
          }
    }
  })
  ->Where(function ($query) use($supmaxi) {
    if( !($supmaxi===Null) ){
          if($supmaxi==true){
            return $query
            ->whereRaw('articles.stock > articles.stock_max'); 
          }
    }
  })

  ->select('articles.*','fournisseurs.nom','art_emplacements.emplacement','art_familles.famille','art_fam_caras.name_famille')
  ->orderBy('articles.des', 'desc')
  ->distinct('articles.ArticleID')
  ->get();
    

$myRequest = new \Illuminate\Http\Request();
$myRequest->setMethod('POST');
$myRequest->request->add(['source' => 'affichage']);


if(!empty($dynamicfilter)){
$articles=$articles->filter(function ($value, $key) use($dynamicfilter,$myRequest) {
  return $this->toFilter($myRequest,$value['ArticleID'],$dynamicfilter);
});
}

//return response()->json(gettype($test));
$myarray=Array();
foreach($articles as $article){
 array_push($myarray,$article);
}
//$articles=$this->myFilterEnCmd($articles,$encmd);
$countQuery = count($myarray); //->count();
$array_after_skipped=array_slice($myarray,$skipped);
$array_take_items=array_slice($array_after_skipped,0,$itemsPerPage);
$articles=$array_take_items;

return response()->json(['articles' => $articles, 'me' => $me, 'total' => $countQuery]);

}


public function myFilterEnCmd($articles,$encmd){
  $articles2=Array();
  foreach($articles as $article){
    $ArticleID=$article['ArticleID'];
    $return=$this->isArticleEnCmd($ArticleID);
  
    if(!($encmd===Null)){
      if($encmd==true){
        if($return){array_push($articles2,$article);}
      }
      if($encmd==false){
        if(!$return){array_push($articles2,$article);}
      }
    }else{
      array_push($articles2,$article);
    }
  }
  return $articles2;
}

public function toFilter($myRequest,$ArticleID,$dynamicfilter){

  
  
  $rets=$this->getarticle($myRequest,$ArticleID);
  if($rets){ 
    if($rets->getData()){
      $cara=$rets->getData()->cara;
    }
   }
 
 //error::insert(['error'=>$ArticleID]);


  if(isset($cara) && !empty($cara) && isset($dynamicfilter) && !empty($dynamicfilter) ){

    //error::insert(['error'=>$ArticleID.'='.json_encode($cara)]);

  foreach($cara as $key => $value){
  
    $name=$value->name; //['name'];
    $input=$value->input; //['input'];
    $number=$value->number; //['number'];
    $valeur=$value->value; //['value'];
    

    //error::insert(['error'=> $name.'='.json_encode($dynamicfilter)]);

    //if( isset($dynamicfilter[$name]) ){
    if( isset($dynamicfilter[$name]) || isset($dynamicfilter[$name.'_____min']) || isset($dynamicfilter[$name.'_____max']) ){
    
    // error::insert(['error'=> json_encode($name)]);

    if($input=='input' && $number==1){

      $numbermin=$dynamicfilter[$name.'_____min'];
      $numbermax=$dynamicfilter[$name.'_____max'];

      if($numbermin!='' && $numbermin!=null){
        if($numbermin>$valeur){return false;}
      }
      if($numbermax!='' && $numbermax!=null){
        if($numbermax<$valeur){return false;}
      }

      //error::insert(['error'=> json_encode($numbermin.','.$numbermax.','.$valeur)]);
    }
    

    if($input=='input' && $number==0){
      if($dynamicfilter[$name]!='' && $dynamicfilter[$name]!=null ){
        if(!$this->contains($valeur,$dynamicfilter[$name])){return false;}
      }
    }

    if($input=='select'){
      if (!in_array($valeur, $dynamicfilter[$name])) {return false;}
    }
    if($input=='radiobutton'){
      if (!in_array($valeur, $dynamicfilter[$name])) {return false;}
    }
    if($input=='checkbox'){
      //$dynamicfilter[$name] tnajjim tkoun null rahoo 
      //if($dynamicfilter[$name]!=null){
       // return false;
      //if(!($valeur==$dynamicfilter[$name])){return false;}
      //}
      //return response()->json($valeur.','.$dynamicfilter[$name]);
      if(! ($dynamicfilter[$name] === Null)){
        if(!($valeur==$dynamicfilter[$name])){return false;}
      }
    }  
  }
   }

   return true;
  }else{
    return true;
  }
}



  public function getformfilter(Request $request, $id)
  {
    $success = true; // na9is taghlif ama misélich 
    $art_fam_cara_dets = art_fam_cara_det::where('fam_cara_id', '=', $id)->get();
    $array_fields = array();

    if ($art_fam_cara_dets) {
      foreach ($art_fam_cara_dets as $art_fam_cara_det) {
        $isInputNumber=false;

        $FamCaraDetID = $art_fam_cara_det->FamCaraDetID;
        $field = new \stdClass();
        $field->type = $art_fam_cara_det->input;
        $field->label = $art_fam_cara_det->label;
        $field->name = $art_fam_cara_det->name;

        $arr_validators = array();

        if ($field->type == 'input') {
           
          /*
        $field->validations = $arr_validators;
        array_push($array_fields, $field);
          */

          $field->inputType = $art_fam_cara_det->type;
          $number = $art_fam_cara_det->number;

          if ($number) {
            $isInputNumber=true;

            $arr_validators1 = array();
            $arr_validators2 = array();

            $field1 = new \stdClass();
            $field1->type = $art_fam_cara_det->input;
            $field1->label = $art_fam_cara_det->label.' Min';
            $field1->name = $art_fam_cara_det->name.'_____min';
    
            $field2 = new \stdClass();
            $field2->type = $art_fam_cara_det->input;
            $field2->label = $art_fam_cara_det->label.' Max';
            $field2->name = $art_fam_cara_det->name.'_____max';

            $validator = new \stdClass();
            $validator->name = 'noname';
            $validator->validator = 'number';
            //$validator->message = $field1->name . 'Que des nombres';
            $validator->message ='Que des nombres';
            array_push($arr_validators1, $validator);
            array_push($arr_validators2, $validator);


            $field1->validations = $arr_validators1;
            $field2->validations = $arr_validators2;
            array_push($array_fields, $field1);
            array_push($array_fields, $field2);

          }

/*
          $field->inputType = $art_fam_cara_det->type;
          $number = $art_fam_cara_det->number;
          if ($number) {
            $validator = new \stdClass();
            $validator->name = 'required';
            $validator->validator = 'number';
            $validator->message = $field->name . 'Que des nombres';
            array_push($arr_validators, $validator);
          }
*/


/*
          $required = $art_fam_cara_det->required;
          if ($required) {
            $validator = new \stdClass();
            $validator->name = 'required';
            $validator->validator = 'Validators.required';
            //$validator->validator='number';
            $validator->message = $field->name . ' Required';
            array_push($arr_validators, $validator);
          }
*/


        }

        if ($field->type == 'select') {
          $field->type='multiple';
          /*
          // systeme da55il forcement valeur par default fil addform 
          $default = art_cara::where('fam_cara_det_id', '=', $FamCaraDetID)->where('default', '=', 1)->select('value')->first();
         // $field->value = [$default->value];
          $field->value = [];
          */

          $arr_options = array();
          $options = art_cara::where('fam_cara_det_id', '=', $FamCaraDetID)->select('value')->get();

          foreach ($options as $option) {
            array_push($arr_options, $option->value);
          }
          $field->options = $arr_options;
          $field->value = $arr_options;
/*
          $required = $art_fam_cara_det->required;
          if ($required) {
            $validator = new \stdClass();
            $validator->name = 'required';
            $validator->validator = 'Validators.required';
            $validator->message = $field->name . ' Required';
            array_push($arr_validators, $validator);
          }
*/
        }


        if ($field->type == 'radiobutton') {
          $field->type='multiple';

          /*
          $default = art_cara::where('fam_cara_det_id', '=', $FamCaraDetID)->where('default', '=', 1)->select('value')->first();
          if ($default) {
           // $field->value = [$default->value];
            $field->value = [];
          } else {
            // first valuer houwa elli par default 
            $default = art_cara::where('fam_cara_det_id', '=', $FamCaraDetID)->select('value')->first();
            if ($default) {
              //$field->value = $default->value;
              $field->value = [];
            }
          }
          */

          $arr_options = array();
          $options = art_cara::where('fam_cara_det_id', '=', $FamCaraDetID)->select('value')->get();
          foreach ($options as $option) {
            array_push($arr_options, $option->value);
          }
          $field->options = $arr_options;
          

          $field->value = $arr_options;
        }

        if ($field->type == 'checkbox') {
          $default = art_cara::where('fam_cara_det_id', '=', $FamCaraDetID)->where('default', '=', 1)->select('value')->first();
          if ($default) {
           // $field->value = $default->value;
            $field->value = false;
          }
        }

      if(!$isInputNumber){
        $field->validations = $arr_validators;
        array_push($array_fields, $field);
      }

      }
    } else {
      $success = false;
    }

    if ($success) {
      return response()->json($array_fields);
    } else {
      return response()->json($success);
    }
  }




function contains($str,$contain)
{
    if(stripos($contain,"|") !== false)
        {
        $s = preg_split('/[|]+/i',$contain);
        $len = sizeof($s);
        for($i=0;$i < $len;$i++)
            {
            if(stripos($str,$s[$i]) !== false)
                {
                return(true);
                }
            }
        }
    if(stripos($str,$contain) !== false)
        {
        return(true);
        }
  return(false);
}








public function getOutilTables(Request $request , $id){

  $etats=art_etat::all();
  $secteurs=art_secteur::all();
  $utilisations=art_utilisation::all();
  $types=art_type::all();

  if($id==0){
  $outil=[];
  $ListeValueUtilisations=[];
  }
  if($id>0){

    $outil=art_outil::where('OutilID','=',$id)->first();
    $ListeUtilisations=art_outil_utilisation::where('outil_id','=',$id)
    ->join('art_utilisations', function ($join){
      $join->on('art_utilisations.UtilisationID', '=', 'art_outil_utilisations.utilisation_id');
    })
    ->select('art_outil_utilisations.utilisation_id','art_utilisations.utilisation')
    ->get();
    
    $ListeValueUtilisationsArray=Array();
    foreach($ListeUtilisations as $value){
    array_push($ListeValueUtilisationsArray,$value['utilisation_id']);
    }
    $ListeValueUtilisations=$ListeValueUtilisationsArray;

  }
  
  //return response()->json(['outil'=>$outil,'ListeUtilisations'=>$ListeUtilisations,'ListeValueUtilisations'=>$ListeValueUtilisations,'etats'=>$etats,'types'=>$types,'utilisations'=>$utilisations,'secteurs'=>$secteurs]);
  return response()->json(['outil'=>$outil,'ListeValueUtilisations'=>$ListeValueUtilisations,'etats'=>$etats,'types'=>$types,'utilisations'=>$utilisations,'secteurs'=>$secteurs]);

}


public function addOutil(Request $request){

  $success = true;
  $msg="L'outil est ajoutée avec succées.";
  $OutilForm = $request->input('OutilForm');
  $ListeValueUtilisations=$request->input('ListeValueUtilisations');

  //$date = $this->DateFormYMD($form['date']);
  
  if($OutilForm['achat']){$OutilForm['achat']=$this->DateFormYMD($OutilForm['achat']);}
  if($OutilForm['FinGarentie']){$OutilForm['FinGarentie']=$this->DateFormYMD($OutilForm['FinGarentie']);}
  if($OutilForm['MiseEnService']){$OutilForm['MiseEnService']=$this->DateFormYMD($OutilForm['MiseEnService']);}

  $retourAddOutil = art_outil::create($OutilForm);
  if(!$retourAddOutil){$success=false; $msg="Error;";}
  
  if($retourAddOutil){
   $OutilID=$retourAddOutil->OutilID;
   foreach($ListeValueUtilisations as $value){
    $art_outil_utilisation=art_outil_utilisation::create(['outil_id'=>$OutilID,'utilisation_id'=>$value]);
    if(!$art_outil_utilisation){$success=false;$msg="Error;";}
   }
  }


    return response()->json(['success'=>$success,'msg'=>$msg,'OutilID'=>$retourAddOutil->OutilID]);
}


public function getOutil($id){
  $success = true;
  $art_outil=art_outil::where('OutilID','=',$id)->first();
  return response()->json($art_outil);
}


public function editOutil(Request $request,$id){
  $success = true;
  $msg="L'outil est modifié avec succées";

  $OutilForm = $request->input('OutilForm');
  $ListeValueUtilisations=$request->input('ListeValueUtilisations');
  
  $OutilForm['achat']=$this->DateFormYMD($OutilForm['achat']);
  $OutilForm['FinGarentie']=$this->DateFormYMD($OutilForm['FinGarentie']);
  $OutilForm['MiseEnService']=$this->DateFormYMD($OutilForm['MiseEnService']);


  $OutilID =$id;
  $retourEditOutil=art_outil::where('OutilID','=',$OutilID)->update($OutilForm);
  //if(!$retourEditOutil){$success=false;}
  //if($retourEditOutil){
    art_outil_utilisation::where('outil_id','=',$OutilID)->delete();
    foreach($ListeValueUtilisations as $value){
     $art_outil_utilisation=art_outil_utilisation::create(['outil_id'=>$OutilID,'utilisation_id'=>$value]);
     if(!$art_outil_utilisation){$success=false; $msg="Error";}
    }
   //}

  return response()->json(['success'=>$success,'msg'=>$msg]);
}

public function deleteOutil($id){

  $success=true;
  $art_outil=art_outil::where('OutilID','=',$id)->update(['exist'=>0]);
  if(!$art_outil){$success=false;}
  return response()->json(['success'=>$success]);

}

public function DateFormYMD($date){
  $date = Carbon::parse($date);
  //$date->hour(5)->minute(5)->second(0);
 // $date->addDays(1);
  $date= $date->format('Y-m-d');
  return $date;
}


//public function getOutils(Request $request,$corbeille){
public function getOutldlkjsdkfjlkdj(Request $request,$corbeille){
  
  $exist=!$corbeille;

    $art_outils=art_outil:: where('art_outils.exist','=',$corbeille)
    ->leftJoin('art_types', function($join) {
      $join->on('art_types.TypeID', '=', 'art_outils.type_id');
    }) //->whereNull('art_outils.type_id')
    ->leftJoin('art_secteurs', function ($join){
      $join->on('art_secteurs.SecteurID', '=', 'art_outils.secteur_id');
    })
    ->leftJoin('art_etats', function ($join){
      $join->on('art_etats.EtatID', '=', 'art_outils.etat_id');
    })
    ->select('art_outils.*','art_types.type','art_etats.etat','art_secteurs.secteur')
    ->get();
 
  return response()->json($art_outils);


}


  //public function filterOutils(Request $request,$corbeille){
  public function getOutils(Request $request,$corbeille){
  
  $tab_bons='art_outils';
  $exist=!$corbeille;

  $page=$request->input('page');
  $itemsPerPage=$request->input('itemsPerPage');
  //$nodes = $request->input('nodes');
  $skipped = ($page - 1) * $itemsPerPage;
  $endItem = $skipped + $itemsPerPage;
  
  $filter=$request->input('filter');
  $searchFilterText=$filter['searchFilterText'];
  $Reserved=$filter['Reserved'];
  $NonReserved=$filter['NonReserved'];

  //$statut=$filter['statut'];
  /*$datemin=$filter['datemin'];
  $datemax=$filter['datemax'];
  $datemin=app('App\Http\Controllers\Divers\generale')->ReglageDateMinDateMax($datemin,'datemin');
  $datemax=app('App\Http\Controllers\Divers\generale')->ReglageDateMinDateMax($datemax,'datemax');*/

    /*
    $Reserved=$request->input('Reserved');
    $NonReserved=$request->input('NonReserved');
    $searchFilterText=$request->input('searchFilterText');
    */
   

    $UserID = JWTAuth::user()->id;
    $me = app('App\Http\Controllers\Divers\generale')->getUserPosts($UserID);
    $me = $me->original;
    $me = $me[0];
    $posts = $me->posts;

    
 
 $outils=art_outil::
 where('art_outils.exist','=',$exist)
 ->Where(function ($query) use($Reserved,$NonReserved,$tab_bons) {
    if( $Reserved == $NonReserved){
       return $query
       ->where($tab_bons.'.reserve','=', 1)
       ->orWhere($tab_bons.'.reserve','=', 0);
    }
    else if (  $Reserved=='true' ){ return $query->where($tab_bons.'.reserve','=', 1); }
    else { return $query->where($tab_bons.'.reserve','=', 0); } 
  })

  ->leftJoin('art_types', function($join) use($tab_bons) {
    $join->on('art_types.TypeID', '=', $tab_bons.'.type_id');
  }) 
  ->leftJoin('art_secteurs', function ($join) use($tab_bons) {
    $join->on('art_secteurs.SecteurID', '=', $tab_bons.'.secteur_id');
  })
  ->leftJoin('art_etats', function ($join) use($tab_bons) {
    $join->on('art_etats.EtatID', '=', $tab_bons.'.etat_id');
  })

  ->Where(function ($query) use($searchFilterText,$tab_bons)  {
      return $query   
      
            ->where('art_types.type','like','%'.$searchFilterText.'%')
            ->orWhere('art_secteurs.secteur','like','%'.$searchFilterText.'%')
            ->orWhere('art_etats.etat','like','%'.$searchFilterText.'%')
            ->orWhere($tab_bons.'.des','like','%'.$searchFilterText.'%')
            ->orWhere($tab_bons.'.code_outil','like','%'.$searchFilterText.'%')
            ->orWhere($tab_bons.'.num_serie','like','%'.$searchFilterText.'%')
            ->orWhere($tab_bons.'.num_modele','like','%'.$searchFilterText.'%');
   })
  ->select($tab_bons.'.*','art_types.type','art_etats.etat','art_secteurs.secteur')
  ->orderBy($tab_bons.'.created_at', 'desc')
  ->distinct($tab_bons.'.OutilID');

  //->get();

    $countQuery = $outils->count();
    $outils = $outils->skip($skipped)->take($itemsPerPage)->get();
    
    return response()->json(['outils' => $outils, 'me' => $me, 'total' => $countQuery]);
  
/*
  if($corbeille==1){
    $cmd_receptions=art_corb_outil::
    Where(function ($query) use($Reserved,$NonReserved,$tab_bons) {
       if( $Reserved == $NonReserved){
          return $query
          ->where($tab_bons.'.reserve','=', 1)
          ->orWhere($tab_bons.'.reserve','=', 0);
       }
       else if (  $Reserved=='true' ){ return $query->where($tab_bons.'.reserve','=', 1); }
       else { return $query->where($tab_bons.'.reserve','=', 0); } 
     })
   
     ->leftJoin('art_types', function($join) use($tab_bons) {
       $join->on('art_types.TypeID', '=', $tab_bons.'.type_id');
     }) 
     ->leftJoin('art_secteurs', function ($join) use($tab_bons) {
       $join->on('art_secteurs.SecteurID', '=', $tab_bons.'.secteur_id');
     })
     ->leftJoin('art_etats', function ($join) use($tab_bons) {
       $join->on('art_etats.EtatID', '=', $tab_bons.'.etat_id');
     })
   
     ->Where(function ($query) use($searchFilterText,$tab_bons)  {
         return $query   
               ->where('art_types.type','like','%'.$searchFilterText.'%')
               ->orWhere('art_secteurs.secteur','like','%'.$searchFilterText.'%')
               ->orWhere('art_etats.etat','like','%'.$searchFilterText.'%')
               ->orWhere($tab_bons.'.des','like','%'.$searchFilterText.'%')
               ->orWhere($tab_bons.'.code_outil','like','%'.$searchFilterText.'%')
               ->orWhere($tab_bons.'.num_serie','like','%'.$searchFilterText.'%')
               ->orWhere($tab_bons.'.num_modele','like','%'.$searchFilterText.'%');
      })
     ->select($tab_bons.'.*','art_types.type','art_etats.etat','art_secteurs.secteur')
     ->orderBy($tab_bons.'.created_at', 'desc')
     ->distinct($tab_bons.'.OutilID')
     ->get();
     }
  */
 // return response()->json($cmd_receptions);
  
}





public function getUseAndTables(Request $request , $id){
  
  $source= $request->input('source');
  $outils=art_outil::where('reserve','=',0)->get(); //all();
  $les_unites=les_unite::all();
  $equipements=equipement::all();
  $intervenants=intervenant::all();
  $isDeleted=false;
  $duree=0;

  if($id==0){
  $use=[];
  }
  if($id>0){
    


    if($source=='modification' || $source=='ajouter'){
      $use=art_use::where('UseID','=',$id)->first();
    }

    if($source=='affichage'){


      $use=art_use::where('UseID','=',$id)
      ->join('intervenants', function ($join){
        $join->on('intervenants.IntervenantID', '=', 'art_uses.intervenant_id');
      })
      ->join('art_outils', function ($join){
      $join->on('art_outils.OutilID', '=','art_uses.outil_id');
      })
      ->select('art_uses.*','art_outils.des','intervenants.name')
      ->first();

      if(!$use){

        $use=art_corb_use::where('UseID','=',$id)
        ->join('intervenants', function ($join){
          $join->on('intervenants.IntervenantID', '=', 'art_corb_uses.intervenant_id');
        })
        ->join('art_outils', function ($join){
        $join->on('art_outils.OutilID', '=','art_corb_uses.outil_id');
        })
        ->select('art_corb_uses.*','art_outils.des','intervenants.name')
        ->first();
        
        if($use){ $isDeleted=true; }

      }

    
    }

   
   
    $date_cloture=$use->date_cloture;
    if($date_cloture!=null){
    $date=$use->date;
    $heure=$use->heure;
    $minute=$use->minute;
    $datecomplet= Carbon::parse($date);
    $datecomplet->hour($heure)->minute($minute)->second(0);
    $duree=$datecomplet->diffInMinutes($date_cloture,false);
    }
  }
  
  return response()->json(['isDeleted'=>$isDeleted,'use'=>$use,'outils'=>$outils,'duree'=>$duree,'les_unites'=>$les_unites,'equipements'=>$equipements,'intervenants'=>$intervenants]);

}

public function addUse(Request $request){
  $success=true;
  $UseForm=$request->input('UseForm');
  $UseForm['date']=$this->DateFormYMD($UseForm['date']);
  $retourAddUse = art_use::create($UseForm);
  if(!$retourAddUse){$success=false;}
  return response()->json($success);
}

public function editUse(Request $request,$id){
  $success=true;
  $UseForm=$request->input('UseForm');
  $UseForm['date']=$this->DateFormYMD($UseForm['date']);
  $retourEditUse = art_use::where('UseID','=',$id)->update($UseForm);
  $retourIncrement = art_use::where('UseID','=',$id)->increment('NbDeModif',1);
  if(!$retourEditUse || !$retourIncrement){$success=false;}
  return response()->json($success);
}

public function deleteUse($id){
  $success=true;

  $art_use=art_use::where('UseID','=',$id)->first();
  $INSERT_art_corb_use=art_corb_use::insert($art_use->toArray());
  if(!$INSERT_art_corb_use){$success=false;}
  
  if($INSERT_art_corb_use){
  $Delete_art_use=art_use::where('UseID','=',$id)->delete();
  if(!$Delete_art_use){$success=false;}
  }
  return response()->json($success);
}

public function getUseskldjskjdlkfjd($corbeille){
  $start = new Carbon('first day of last month'); $start->hour(0)->minute(0)->second(0);
  if($corbeille==1){
    $uses=art_corb_use::whereDate('art_corb_uses.created_at','>=',$start)
    ->join('art_outils', function ($join){
     $join->on('art_outils.OutilID', '=','art_corb_uses.outil_id');
    })
    ->join('intervenants', function ($join){
      $join->on('intervenants.IntervenantID', '=','art_corb_uses.intervenant_id');
     })
    ->select('art_corb_uses.*','art_outils.des','intervenants.name')
    ->orderBy('art_corb_uses.created_at', 'desc')
    ->get();
  }else{
    $uses=art_use::whereDate('art_uses.created_at','>=',$start)
    ->join('art_outils', function ($join){
      $join->on('art_outils.OutilID', '=','art_uses.outil_id');
    })
     ->join('intervenants', function ($join){
       $join->on('intervenants.IntervenantID', '=','art_uses.intervenant_id');
      })
     ->select('art_uses.*','art_outils.des','intervenants.name')
    ->orderBy('art_uses.created_at', 'desc')
    ->get();
  }
  return response()->json($uses);
}


public function getUses(Request $request,$corbeille){


  $page=$request->input('page');
  $itemsPerPage=$request->input('itemsPerPage');
  //$nodes = $request->input('nodes');
  $skipped = ($page - 1) * $itemsPerPage;
  $endItem = $skipped + $itemsPerPage;
  
  $filter=$request->input('filter');
  $searchFilterText=$filter['searchFilterText'];
  $Opened=$filter['Opened'];
  $NonOpened=$filter['NonOpened'];
  //$statut=$filter['statut'];
  $datemin=$filter['datemin'];
  $datemax=$filter['datemax'];
  $datemin=app('App\Http\Controllers\Divers\generale')->ReglageDateMinDateMax($datemin,'datemin');
  $datemax=app('App\Http\Controllers\Divers\generale')->ReglageDateMinDateMax($datemax,'datemax');

    $UserID = JWTAuth::user()->id;
    $me = app('App\Http\Controllers\Divers\generale')->getUserPosts($UserID);
    $me = $me->original;
    $me = $me[0];
    $posts = $me->posts;






  if($corbeille==0){
    $tab_bons='art_uses';
  }else if($corbeille==1){
    $tab_bons='art_corb_uses';
  }else{
    return response()->json([]);
  }

  /*
    $Opened=$request->input('Opened');
    $NonOpened=$request->input('NonOpened');
    $searchFilterText=$request->input('searchFilterText');
    $datemin=$request->input('datemin');
    $datemax=$request->input('datemax');
*/

   // $start = new Carbon('first day of last month'); $start->hour(0)->minute(0)->second(0);
    
    /*
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
 $uses=art_use::
   whereDate($tab_bons.'.created_at','>',$datemin)
 ->whereDate($tab_bons.'.created_at','<=',$datemax)
 ->Where(function ($query) use($Opened,$NonOpened,$tab_bons) {
    if( $Opened== $NonOpened){
       return $query
       ->where($tab_bons.'.isOpened','=', 1)
       ->orWhere($tab_bons.'.isOpened','=', 0);
    }
    else if (  $Opened=='true' ){ return $query->where($tab_bons.'.isOpened','=', 1); }
    else { return $query->where($tab_bons.'.isOpened','=', 0); } 
  })
  ->join('intervenants', function ($join)use($tab_bons){
        $join->on('intervenants.IntervenantID', '=', $tab_bons.'.intervenant_id');
    })
  ->join('art_outils', function ($join)use($tab_bons){
      $join->on('art_outils.OutilID', '=', $tab_bons.'.outil_id');
  })
  ->Where(function ($query) use($searchFilterText){
      return $query   
            ->where('intervenants.name','like','%'.$searchFilterText.'%')
            ->orWhere('art_outils.des','like','%'.$searchFilterText.'%')
            ->orWhere('art_outils.code_outil','like','%'.$searchFilterText.'%')
            ->orWhere('art_outils.num_serie','like','%'.$searchFilterText.'%');
   })
  ->select($tab_bons.'.*','intervenants.name','art_outils.des')
  ->orderBy($tab_bons.'.created_at', 'desc')
  ->distinct($tab_bons.'.UseID');
  
  //->get();

  $countQuery = $uses->count();
  $uses = $uses->skip($skipped)->take($itemsPerPage)->get();
  
  return response()->json(['uses' => $uses, 'me' => $me, 'total' => $countQuery]);

  }

  if($corbeille==1){
    $uses=art_corb_use::
      whereDate($tab_bons.'.created_at','>',$datemin)
    ->whereDate($tab_bons.'.created_at','<=',$datemax)
    ->Where(function ($query) use($Opened,$NonOpened,$tab_bons) {
       if( $Opened== $NonOpened){
          return $query
          ->where($tab_bons.'.isOpened','=', 1)
          ->orWhere($tab_bons.'.isOpened','=', 0);
       }
       else if (  $Opened=='true' ){ return $query->where($tab_bons.'.isOpened','=', 1); }
       else { return $query->where($tab_bons.'.isOpened','=', 0); } 
     })
  
  ->join('intervenants', function ($join)use($tab_bons){
      $join->on('intervenants.IntervenantID', '=', $tab_bons.'.intervenant_id');
  })

->join('art_outils', function ($join)use($tab_bons){
    $join->on('art_outils.OutilID', '=', $tab_bons.'.outil_id');
})

->Where(function ($query) use($searchFilterText,$tab_bons){
    return $query   
          ->where('intervenants.name','like','%'.$searchFilterText.'%')
          ->orWhere('art_outils.des','like','%'.$searchFilterText.'%')
          ->orWhere('art_outils.code_outil','like','%'.$searchFilterText.'%')
          ->orWhere('art_outils.num_serie','like','%'.$searchFilterText.'%');
 })
 ->select($tab_bons.'.*','intervenants.name','art_outils.des')
  ->orderBy($tab_bons.'.created_at', 'desc')
->distinct($tab_bons.'.UseID');

//->get();

$countQuery = $uses->count();
$uses = $uses->skip($skipped)->take($itemsPerPage)->get();

return response()->json(['uses' => $uses, 'me' => $me, 'total' => $countQuery]);

     }

//return response()->json($cmd_receptions);
  
}


public function cloture(Request $request,$id){
 $now= new Carbon('');
 art_use::where('UseID','=',$id)->update(['isOpened'=>0,'date_cloture'=>$now]);
}






public function getArticlesHasSFamID($id){
 $art_fam_cara=art_fam_cara::where('FamCaraID','=',$id)->select('famille_id')->first();
 $SousFamilleID=$id;
 $FamilleID=$art_fam_cara->famille_id;

 $articles=article::where('famille_id','=',$FamilleID)->where('sous_famille_id','=',$SousFamilleID)->get();

 return response()->json($articles);
}

public function getArticlesByArtIDs(Request $request){
 $ArtIDs=$request->input('ArtIDs');
 $articles=article::whereIn('ArticleID',$ArtIDs)
 ->join('unites', function ($join){
  $join->on('unites.UniteID', '=', '.articles.unite_id');
 })
 ->select('articles.*','unites.unite')
 ->get();
 return response()->json($articles);

}

public function inventaire(Request $request){
  
  $success=true;
  $arr=$request->input('arr');
  $InterIDs=$request->input('InterIDs');
  $type=$request->input('type');
  $ListeIntervenants="";
  foreach($InterIDs as $IntervenantID){
   $ListeIntervenants.=$IntervenantID.',';
  }

  
  // Enregistrement
  $articleOne=$arr[0];
  $ArticleID=$articleOne['ArticleID'];
  $article=article::where('ArticleID','=',$ArticleID)->first();
  $famille_id=$article->famille_id;
  $sous_famille_id=$article->sous_famille_id;
  
  $art_inventaire=art_inventaire::create([
   'famille_id'=>$famille_id,
   'sous_famille_id'=>$sous_famille_id,
   'ListeIntervenants'=>$ListeIntervenants,
   'type'=>$type,
   'NbArticles'=>count($arr)
  ]);
  $InventaireID=$art_inventaire->InventaireID;
 
  if($art_inventaire){
  
  foreach($arr as $article){
  $ArticleID=$article['ArticleID'];
  $value=$article['value'];
  $AnStock=$this->GetStockByArticleID($ArticleID);
  $art_inventaire_det=art_inventaire_det::create([
    'inventaire_id'=>$InventaireID,
    'article_id'=>$ArticleID,
    'AnStock'=>$AnStock,
    'NvStock'=>$value
  ]);

  if(!$art_inventaire_det){$success=false;}
  }
  


  if($type=='correction'){
  // correction 
  }


  }else{
    $success=false;
  }

  return response()->json($success);

}


public function getIntervenantsByInterIDs(Request $request){
  $InterIDs=$request->input('InterIDs');
  $intervenants=intervenant::whereIn('IntervenantID',$InterIDs)->get();
  return response()->json($intervenants);
}



public function getInventaire($id){

  $InventaireID=$id;
  $art_inventaire=art_inventaire::where('InventaireID','=',$InventaireID)
  ->join('art_familles', function ($join){
    $join->on('art_familles.FamilleID', '=', 'art_inventaires.famille_id');
  })
  ->join('art_fam_caras', function ($join){
    $join->on('art_fam_caras.FamCaraID', '=', 'art_inventaires.sous_famille_id');
  })
  ->select('art_inventaires.*','art_familles.famille','art_fam_caras.name_famille')
  ->first();

  $art_inventaire_det=art_inventaire_det::where('inventaire_id','=',$InventaireID)
  ->join('articles', function ($join){
    $join->on('articles.ArticleID', '=', 'art_inventaire_dets.article_id');
  })
  ->select('art_inventaire_dets.*','articles.des')
  ->get();

  $ListeIntervenants=$art_inventaire->ListeIntervenants;
  $arrIntervenants=explode(',',$ListeIntervenants,-1);
  $intervenants=intervenant::whereIn('IntervenantID',$arrIntervenants)->get();

  return response()->json(['intervenants'=>$intervenants,'inventaire'=>$art_inventaire,'inventaireDet'=>$art_inventaire_det]);

}

public function getInventaires(){
  $start = new Carbon('first day of last month'); $start->hour(0)->minute(0)->second(0);
  $inventaires=art_inventaire::where('art_inventaires.created_at','>=',$start)
  ->join('art_familles', function ($join){
    $join->on('art_familles.FamilleID', '=', 'art_inventaires.famille_id');
  })
  ->join('art_fam_caras', function ($join){
    $join->on('art_fam_caras.FamCaraID', '=', 'art_inventaires.sous_famille_id');
  })
  ->select('art_inventaires.*','art_familles.famille','art_fam_caras.name_famille')
  ->get();

  $intervenants=intervenant::all();
  return response()->json(['inventaires'=>$inventaires,'intervenants'=>$intervenants]);
}


public function filterInventaires(Request $request){

    $Enregistrement=$request->input('Enregistrement');
    $Correction=$request->input('Correction');
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
    
 $inventaires=art_inventaire::
 whereDate('art_inventaires.created_at','>',$datemin)
 ->whereDate('art_inventaires.created_at','<=',$datemax)

 ->Where(function ($query) use($Enregistrement,$Correction) {
   return $query
   ->Where(function ($query) use($Enregistrement) {
      if( ( $Enregistrement=='true' ) ){ return $query->where('art_inventaires.type','=', 'enregistrement'); }else{return $query->where('art_inventaires.type','=', 'rien');}
    })
    ->orWhere(function ($query) use($Correction) {
      if( ( $Correction=='true' ) ){ return $query->where('art_inventaires.type','=', 'correction'); }else{return $query->where('art_inventaires.type','=', 'rien');}
    });
  })

  ->join('art_familles', function ($join){
    $join->on('art_familles.FamilleID', '=', 'art_inventaires.famille_id');
    })
  ->join('art_fam_caras', function ($join) {
        $join->on('art_fam_caras.FamCaraID', '=', 'art_inventaires.sous_famille_id');
    })
  ->join('art_inventaire_dets', function ($join) {
      $join->on('art_inventaire_dets.inventaire_id', '=', 'art_inventaires.InventaireID');
  })
  ->join('articles', function ($join){
    $join->on('articles.ArticleID', '=', 'art_inventaire_dets.article_id');
  })
  ->Where(function ($query) use($searchFilterText){
      return $query   
            ->where('art_familles.famille','like','%'.$searchFilterText.'%')
            ->orWhere('art_fam_caras.name_famille','like','%'.$searchFilterText.'%')
            ->orWhere('articles.des','like','%'.$searchFilterText.'%')
            ->orWhere('articles.code_article','like','%'.$searchFilterText.'%')
            ->orWhere('articles.code_a_barre','like','%'.$searchFilterText.'%'); 
   })
  
  ->select('art_inventaires.*','art_familles.famille','art_fam_caras.name_famille')
  ->orderBy('art_inventaires.created_at', 'desc')
  ->distinct('art_inventaires.InventaireID')
  ->get();

  return response()->json($inventaires);
}

public function correction($id){
  
  $success=true;

  $art_inventaire_dets=art_inventaire_det::where('inventaire_id','=',$id)->get();
  foreach($art_inventaire_dets as $art_inventaire_det){
   $ArticleID=$art_inventaire_det['article_id'];
   $AnStock=$art_inventaire_det['AnStock'];
   $NvStock=$art_inventaire_det['NvStock'];
   $Ecart=$NvStock-$AnStock;

  
   $art_pps=art_pp::where('article_id','=',$ArticleID)->where('exist','=',true)->get();
   if($art_pps){
   $retourPUMP=$this->CalculerPUMP($art_pps,0,0);
   $AnPUMP=$retourPUMP['AnPUMP'];
   
   
   $type='correction';
   $TypeID='InventaireID';
   $ID=$id;
   $retPUMP=$this->PUMP($ArticleID,$TypeID,$ID,$type,$Ecart,$AnPUMP);
   if(!$retPUMP){$success=false;}

   $correction=$this->UpdateStockByArticleID($ArticleID,$Ecart);
  if($correction){
  $update=art_inventaire::where('InventaireID','=',$id)->update(['type'=>'correction']);
  if(!$update){$success=false;}
  }else{
    $success=false;
  }

   }else{
     $success=false;
   }
  }
 return response()->json($success);
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

public function CalculerPUMP($art_pps,$Qte,$PrixHT){ 
  $TTPrxiHT=0;
  $TTQte=0;
  foreach($art_pps as $art_pp){
    $NvQte=$art_pp['NvQte']; $NvPrixHT=$art_pp['NvPrixHT'];
    $TTPrxiHT+=$NvQte*$NvPrixHT;
    $TTQte+=$NvQte;
  }
  $AnPUMP=$TTPrxiHT/$TTQte;
  if($Qte==0){
  $NvPUMP=$AnPUMP;
  }else{
  $TTPrxiHT+=$Qte*$PrixHT;
  $TTQte+=$Qte;
  $NvPUMP=$TTPrxiHT/$TTQte;
  }
  return ['AnPUMP'=>$AnPUMP,'NvPUMP'=>$NvPUMP] ;
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






}
