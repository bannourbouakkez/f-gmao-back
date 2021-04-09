<?php

namespace App\Http\Controllers\Equipement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Model\Equipement\equi_equipement;
use App\Model\Equipement\equi_equipementdeu;
use App\Model\Equipement\equi_niveau;
use App\Model\Equipement\equi_anomalie;
use App\Model\Equipement\equi_tache;
use App\Model\Equipement\equi_anomalies_equipement;
use App\Model\Equipement\equi_taches_equipement;
use App\Model\Equipement\equi_magasin_article;
use App\Model\Equipement\equi_atelier_article;

use App\Model\Divers\date_time_test;

use Carbon\Carbon;
use DateTime;
use phpDocumentor\Reflection\Types\Boolean;

class equipement extends Controller
{
  /**
   * Handle the incoming request.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\Response
   */
  /*
    public function __invoke(Request $request)
    {
        //
    }
    */
  // https://sqlpro.developpez.com/cours/arborescence/ 
  //https://angular2-tree.readme.io/docs/styling

  public function NiveauMinMax()
  {
    $equi_niveau = equi_niveau::where('isMax', '=', 1)->where('exist', '=', 1)->first();
    $NiveauMax = $equi_niveau->niveau;

    $equi_niveau2 = equi_niveau::where('isMin', '=', 1)->where('exist', '=', 1)->first();
    $NiveauMin = $equi_niveau2->niveau;

    return ['NiveauMin' => $NiveauMin, 'NiveauMax' => $NiveauMax];
  }

  public function getNiveauMax()
  {
    $equi_niveau = equi_niveau::where('isMax', '=', 1)->where('exist', '=', 1)->first();
    $NiveauMax = $equi_niveau->niveau;
    return response()->json($NiveauMax);
  }

  public function getNodeDet($NodeID)
  {

    $NiveauMinMax = $this->NiveauMinMax();
    $NiveauMax = $NiveauMinMax['NiveauMax'];

    $equi_equipement = equi_equipement::where('EquipementID', '=', $NodeID)->where('equi_equipements.exist', '=', 1)->first();


    if ($equi_equipement && ($equi_equipement->Niv < $NiveauMax)) {

      $equi_niveau = equi_niveau::where('niveau', '=', $equi_equipement->Niv + 1)->first();
      $NextNiveauName = $equi_niveau->nom;
      return response()->json(['Node' => $equi_equipement, 'NextNiveauName' => $NextNiveauName]);
    } else {
      return response()->json(false);
    }
  }

  public function addEquipement(Request $request, $PereID)
  {
    $success = true;
    $nom = $request->input('name');


    //--- Tester L'unicite du nom
    $IsUniqueNom = $this->IsUniqueNom($PereID, $nom);
    if ($IsUniqueNom == false) {
      $success = false;
      return response()->json($success);
    }
    //###########################

    $Pere = equi_equipement::where('EquipementID', '=', $PereID)->where('exist', '=', 1)->first();
    $Pere_BG = $Pere->BG;
    $Pere_BD = $Pere->BD;
    $Niv = $Pere->Niv;

    $equi_niveau = equi_niveau::where('niveau', '=', $Niv + 1)->where('exist', '=', 1)->first();
    $Nv_niveau_id = $equi_niveau->NiveauID;

    if ($Pere && $equi_niveau) {

      $increment1 = equi_equipement::where('BD', '>=', $Pere_BD)->where('exist', '=', 1)->increment('BD', 2);
      $increment2 = equi_equipement::where('BG', '>=', $Pere_BD)->where('exist', '=', 1)->increment('BG', 2);

      $Nv_BG = $Pere_BD;
      $Nv_BD = $Pere_BD + 1;
      $Nv_Niv = $Niv + 1;

      //if($increment1 && $increment2){
      $create = equi_equipement::create([
        'BG' => $Nv_BG,
        'BD' => $Nv_BD,
        'Niv' => $Nv_Niv,
        'niveau_id' => $Nv_niveau_id,
        'equipement' => $nom,
        'exist' => true
      ]);

      if (!$create) {
        $success = false;
        return response()->json('B');
      } else {
        $EquipementID = $create->EquipementID;
        equi_equipementdeu::insert(['EquipementID'=>$EquipementID]);
      }
      //}else{$success=false;return response()->json('C');

    } else {
      $success = false;
      return response()->json('D');
    }

    if (!$success) {
      return response()->json($success);
    } else {
      return response()->json($EquipementID);
    }
  }


  public function deleteEquipement($id)
  {
    $success = true;

    $equi_equipement = equi_equipement::where('EquipementID', '=', $id)->where('exist', '=', 1)->first();
    $BG = $equi_equipement->BG;
    $BD = $equi_equipement->BD;
    if (($BD - $BG) >= 1) {
      $isFeuille = false;
    }
    if (($BD - $BG) == 1) {
      $isFeuille = true;
    }

    if ($equi_equipement) {
      if ($isFeuille) {
        //equi_equipement::where('BG','=',$BG)->where('exist','=',1)->delete();
        $e1 = equi_equipement::where('BG', '=', $BG)->where('exist', '=', 1)->update(['exist' => 0]);
        //if($e1){
        $e2 = equi_equipement::where('BG', '>=', $BG)->where('exist', '=', 1)->increment('BG', -2);
        //if($e2){
        $e3 = equi_equipement::where('BD', '>=', $BD)->where('exist', '=', 1)->increment('BD', -2);
        //if(!$e3){$success=false;}
        //}else{$success=false; }
        //}else{ $success=false;}
      }

      if (!$isFeuille) {
        //equi_equipement::where('BG','>=',$BG)->where('BD','<=',$BD)->where('exist','=',1)->delete();
        $e4 = equi_equipement::where('BG', '>=', $BG)->where('BD', '<=', $BD)->where('exist', '=', 1)->update(['exist' => 0]);
        $decalage = $BD - $BG + 1;
        //if($e4){
        $e5 = equi_equipement::where('BD', '>=', $BG)->where('exist', '=', 1)->increment('BD', -$decalage);
        //if($e5){
        $e6 = equi_equipement::where('BG', '>', $BG)->where('exist', '=', 1)->increment('BG', -$decalage);
        //if(!$e6){$success=false;}
        //}else{$success=false;}
        //}else{$success=false;}
      }
    } else {
      $success = false;
    }
    return response()->json($success);
  }


  public function getChilds(Request $request, $id)
  {


    $all = $request->input('all');
    $isAll = new Boolean();
    if ($all == '1' || $all == 1) {
      $isAll = true;
    }
    if ($all == '0' || $all == 0) {
      $isAll = false;
    }
    $isAll = true;

    $equi_equipement = equi_equipement::where('EquipementID', '=', $id)->where('exist', '=', 1)->first();
    $BG = $equi_equipement->BG;
    $BD = $equi_equipement->BD;
    $Niv = $equi_equipement->Niv;


    if (!$isAll) {
      $childs = equi_equipement::whereRaw('equi_equipements.BD - equi_equipements.BG = 1')
        ->where('BG', '>', $BG)->where('BD', '<', $BD)->where('Niv', '=', $Niv + 1)->where('exist', '=', 1)->get();
    } else {
      $childs = equi_equipement::where('BG', '>', $BG)->where('BD', '<', $BD)->where('Niv', '=', $Niv + 1)->where('exist', '=', 1)->get();
    }

    return response()->json($childs);
  }

  public function IsUniqueNom($PereID, $nom)
  {
    $req = new Request();
    $req->merge(['all' => 1]); // $req->request->add(['all' =>true]);
    $getChilds = $this->getChilds($req, $PereID);
    $childs = $getChilds->original;
    foreach ($childs as $child) {
      if (strtolower($nom) == strtolower($child->equipement)) {
        return false;
      }
    }
    return true;
  }

  public function getNode($EquipementID)
  { // No Loading // return all childs without this Node 

    $req = new Request();
    $req->merge(['all' => 1]); // $req->request->add(['all' =>true]);
    $getChilds = $this->getChilds($req, $EquipementID);
    $childs = $getChilds->original;


    if (count($childs) == 0) {
      //}else if( count($childs)==1 ){
      $ret = equi_equipement::find($EquipementID);
      $child = new \stdClass();
      $child->id = $ret->EquipementID;
      $child->name = $ret->equipement;
      $child->depth = $ret->Niv;
      $child->hasChildren = false;
      return $child;
    } else {
      $arr = array();
      foreach ($childs as $child) {
        $req2 = new Request();
        $req2->merge(['all' => 1]); // $req->request->add(['all' =>true]);
        $getChilds2 = $this->getChilds($req2, $child->EquipementID);
        $childs2 = $getChilds2->original;

        if (count($childs2) == 0) {
          array_push(
            $arr,
            [
              'id' => $child->EquipementID,
              'name' => $child->equipement,
              'depth' => $child->Niv,
              'hasChildren' => false
            ]
          );
        } else {
          array_push(
            $arr,
            [
              'id' => $child->EquipementID,
              'name' => $child->equipement,
              'depth' => $child->Niv,
              'hasChildren' => true,
              'children' => $this->getNode($child->EquipementID)
            ]
          );
        }
      }
      return $arr;
    }
  }

  public function getNodeJustChilds($NodeID)
  {
    // Loading // return node avec niveau 1 without this Node
    // =  getNodeByNodeIDAndByNiveau(request[1],$NodeID);

    $req = new Request();
    $req->merge(['all' => 1]); // $req->request->add(['all' =>true]);
    $getChilds = $this->getChilds($req, $NodeID);
    $childs = $getChilds->original;

    if (count($childs) == 0) {
      $ret = equi_equipement::find($NodeID);
      $child = new \stdClass();
      $child->id = $ret->EquipementID;
      $child->name = $ret->equipement;
      $child->hasChildren = false;
      $child->depth = $ret->Niv;
      // $child->children=[]; //zedtha ? 
      return $child;
    } else {
      $arr = array();
      foreach ($childs as $child) {
        $hasChildren = false;
        $getChilds2 = $this->getChilds($req, $child->EquipementID);
        $childs2 = $getChilds2->original;
        if (count($childs2) > 0) {
          $hasChildren = true;
        }
        //if($hasChildren){
        //  array_push( $arr ,  ['id'=>$child->EquipementID,'name'=>$child->equipement,'depth'=>$child->Niv,'hasChildren'=>$hasChildren,'children'=>[] ] );
        array_push($arr,  ['id' => $child->EquipementID, 'name' => $child->equipement, 'depth' => $child->Niv, 'hasChildren' => $hasChildren]);
        //}else{}
      }

      return $arr;
    }
  }



  public function getNodeByNodeIDAndByNiveau(Request $request, $NodeID)
  { // Loading // return node avec niveau without this node Extern Func
    $niveau = $request->input('niveau');
    return  $this->NodeByNiveau($NodeID, $niveau, 0);
  }

  public function NodeByNiveau($NodeID, $niveau, $compteur)
  { // Loading // return node avec niveau without this node Intern Func 

    $nvcompteur = $compteur + 1;

    $req = new Request();
    $req->merge(['all' => 1]); // $req->request->add(['all' =>true]);
    $getChilds = $this->getChilds($req, $NodeID);
    $childs = $getChilds->original;

    if (count($childs) == 0) {
      $ret = equi_equipement::find($NodeID);
      $child = new \stdClass();
      $child->id = $ret->EquipementID;
      $child->name = $ret->equipement;
      $child->hasChildren = false;
      $child->depth = $ret->Niv;
      // $child->children=[];
      return $child;
    } else {


      if ($nvcompteur <= $niveau) {
        $arr = array();
        foreach ($childs as $child) {

          $hasChildren = false;
          $getChilds2 = $this->getChilds($req, $child->EquipementID);
          $childs2 = $getChilds2->original;
          if (count($childs2) > 0) {
            $hasChildren = true;
          }


          if ($hasChildren) {
            $children = $this->NodeByNiveau($child->EquipementID, $niveau, $nvcompteur);
          } else {
            // $children = [] ; //[]
          }

          if (isset($children)) {
            array_push(
              $arr,
              [
                'id' => $child->EquipementID,
                'name' => $child->equipement,
                'hasChildren' => $hasChildren,
                'depth' => $child->Niv,
                'children' => $children
              ]
            );
          } else {
            array_push(
              $arr,
              [
                'id' => $child->EquipementID,
                'name' => $child->equipement,
                'hasChildren' => $hasChildren,
                'depth' => $child->Niv
              ]
            );
          }
        }
        return $arr;
      } //else{return [];}


    }
    //return [];
  }

  public function getRacine($niveau)
  { // Loading // get racine Node avec niveau With Racine Node
    $equi_equipement = equi_equipement::where('BG', '=', 1)->first();

    $racine = new \stdClass();
    $racine->id = $equi_equipement->EquipementID;
    $racine->name = $equi_equipement->equipement;
    $racine->depth = $equi_equipement->Niv;
    $hasChildren = false;
    $req = new Request();
    $req->merge(['all' => 1]); // $req->request->add(['all' =>true]);
    $getChilds = $this->getChilds($req, $equi_equipement->EquipementID);
    $childs = $getChilds->original;
    if (count($childs) > 0) {
      $hasChildren = true;

      $req2 = new Request();
      $req2->merge(['niveau' => $niveau]); //$req2->merge(['niveau' => 2]);
      $children = $this->getNodeByNodeIDAndByNiveau($req2, $equi_equipement->EquipementID);
      $racine->children = $children;
    } else {
      //$racine->children=[];  
    }

    $racine->hasChildren = $hasChildren;
    $racine_arr = array();
    array_push($racine_arr, $racine);

    $Niveaux = equi_niveau::all();
    $NiveauMinMax = $this->NiveauMinMax();
    $NiveauMin = $NiveauMinMax['NiveauMin'];
    $NiveauMax = $NiveauMinMax['NiveauMax'];



    return response()->json(['racine' => $racine_arr, 'Niveaux' => $Niveaux, 'NiveauMax' => $NiveauMax, 'NiveauMin' => $NiveauMin]);
  }


  public function getNodeRacine(Request $request, $NodeID)
  { // Loading // get Node Avec niveau With this Node
    $niveau = $request->input('niveau');
    $equi_equipement = equi_equipement::where('EquipementID', '=', $NodeID)->first();

    $racine = new \stdClass();
    $racine->id = $equi_equipement->EquipementID;
    $racine->name = $equi_equipement->equipement;
    $racine->depth = $equi_equipement->Niv;

    $hasChildren = false;
    $req = new Request();
    $req->merge(['all' => 1]); // $req->request->add(['all' =>true]);
    $getChilds = $this->getChilds($req, $equi_equipement->EquipementID);
    $childs = $getChilds->original;
    if (count($childs) > 0) {
      $hasChildren = true;

      $req2 = new Request();
      $req2->merge(['niveau' => $niveau]); //$req2->merge(['niveau' => 2]);
      $children = $this->getNodeByNodeIDAndByNiveau($req2, $equi_equipement->EquipementID);
      $racine->children = $children;
    }

    $racine->hasChildren = $hasChildren;
    //$racine=$racine;
    $racine_arr = array();
    array_push($racine_arr, $racine);
    return response()->json($racine_arr);
  }



  public function getAnomalie($id)
  {

    $equi_anomalie = equi_anomalie::where('AnomalieID', '=', $id)->first();
    $equi_anomalies_equipements = equi_anomalies_equipement::where('anomalie_id', '=', $id)->get();

    $arr = array();
    foreach ($equi_anomalies_equipements as $equiement) {

      $equipement_id = $equiement->equipement_id;
      $e = equi_equipement::where('EquipementID', '=', $equipement_id)->first();

      $node = new \stdClass();
      $data = new \stdClass();
      $data->id = $e->EquipementID;
      $data->name = $e->equipement;
      $data->depth = $e->Niv;
      $node->data = $data;

      /*
        $hasChildren=false;
        $req=new Request();  $req->merge(['all' =>1]);
        $getChilds=$this->getChilds($req,$equiement->EquipementID);
        $childs = $getChilds->original;
        if(count($childs)>0){ $hasChildren=true; }
        $data->hasChildren=false;
        */

      array_push($arr, $node);
    }

    return response()->json(['anomalie' => $equi_anomalie, 'equipements' => $arr]);
  }

  public function getTache($id)
  {

    $equi_tache = equi_tache::where('TacheID', '=', $id)->first();
    $equi_taches_equipements = equi_taches_equipement::where('tache_id', '=', $id)->get();

    $arr = array();
    foreach ($equi_taches_equipements as $equiement) {

      $equipement_id = $equiement->equipement_id;
      $e = equi_equipement::where('EquipementID', '=', $equipement_id)->first();

      $node = new \stdClass();
      $data = new \stdClass();
      $data->id = $e->EquipementID;
      $data->name = $e->equipement;
      $data->depth = $e->Niv;
      $node->data = $data;

      /*
        $hasChildren=false;
        $req=new Request();  $req->merge(['all' =>1]);
        $getChilds=$this->getChilds($req,$equiement->EquipementID);
        $childs = $getChilds->original;
        if(count($childs)>0){ $hasChildren=true; }
        $data->hasChildren=false;
        */

      array_push($arr, $node);
    }

    return response()->json(['tache' => $equi_tache, 'equipements' => $arr]);
  }


  public function getEquipement($id){
    
      $e = equi_equipement::where('EquipementID', '=', $id)->where('exist','=',1)->first();

      $node = new \stdClass();
      $data = new \stdClass();
      $data->id = $e->EquipementID;
      $data->name = $e->equipement;
      $data->depth = $e->Niv;
      $node->data = $data;

      return response()->json($node);
  }


  public function addAnomalie(Request $request, $id)
  {

    $success = true;
    $form = $request->input('form');
    $nodes = $request->input('nodes');

    if ($id > 0) {
      // Modification 
      unset($form['updated_at']);
      $equi_anomalie = equi_anomalie::where('AnomalieID', '=', $id)->update($form);
      equi_anomalies_equipement::where('anomalie_id', '=', $id)->delete();
      foreach ($nodes as $NodeID) {
        equi_anomalies_equipement::create([
          'anomalie_id' => $id,
          'equipement_id' => $NodeID
        ]);
      }


    } else {
      // Ajout
      $equi_anomalie = equi_anomalie::create($form);
      $AnomalieID = $equi_anomalie->AnomalieID;
      if (!$equi_anomalie) {
        $success = false;
      }

      foreach ($nodes as $NodeID) {
        equi_anomalies_equipement::create([
          'anomalie_id' => $AnomalieID,
          'equipement_id' => $NodeID
        ]);
      }
      
      return response()->json($equi_anomalie);
      //return response()->json($equi_anomalie->AnomalieID);


    }
 
    return response()->json($success);

    
  }

  public function addTache(Request $request, $id)
  {

    $success = true;
    $form = $request->input('form');
    $nodes = $request->input('nodes');

    if ($id > 0) {
      // Modification 
      unset($form['updated_at']);
      $equi_tache = equi_tache::where('TacheID', '=', $id)->update($form);
      equi_taches_equipement::where('tache_id', '=', $id)->delete();
      foreach ($nodes as $NodeID) {
        equi_taches_equipement::create([
          'tache_id' => $id,
          'equipement_id' => $NodeID
        ]);
      }


    } else {
      // Ajout
      $equi_tache = equi_tache::create($form);
      $TacheID = $equi_tache->TacheID;
      if (!$equi_tache) {
        $success = false;
      }

      foreach ($nodes as $NodeID) {
        equi_taches_equipement::create([
          'tache_id' => $TacheID,
          'equipement_id' => $NodeID
        ]);
      }
      
      return response()->json($equi_tache);
      //return response()->json($equi_anomalie->AnomalieID);


    }
 
    return response()->json($success);

    
  }



  public function getSemblables(Request $request, $quoi)
  {
    $precision = 60;
    $arr = array();
    $isStrict = false;
    $value = $request->input('value');
    $strict = $request->input('strict');
    if ($strict == '1' || $strict == 1) {
      $isStrict = true;
    }

    if ($quoi == 'anomalies') {
      if (!$isStrict) {
        $semblables = equi_anomalie::where('exist','=',1)->get();
        foreach ($semblables as $semblable) {
          $anomalie = $semblable->anomalie;
          if ($this->similar_text($anomalie, $value, $precision) > $precision) {
            array_push($arr, $semblable);
          }
        }
      }
      //if(strlen($value)>=3){
      $semblables = equi_anomalie::where('anomalie', 'LIKE', '%' . $value . '%')->get();
      foreach ($semblables as $semblable) {
        if (!in_array($semblable, $arr)) {
          array_push($arr, $semblable);
        }
      }
      //}
    }


    if ($quoi == 'taches') {
      if (!$isStrict) {
        $semblables = equi_tache::where('exist','=',1)->get();
        foreach ($semblables as $semblable) {
          $tache = $semblable->tache;
          if ($this->similar_text($tache, $value, $precision) > $precision) {
            array_push($arr, $semblable);
          }
        }
      }
      //if(strlen($value)>=3){
      $semblables = equi_tache::where('tache', 'LIKE', '%' . $value . '%')->get();
      foreach ($semblables as $semblable) {
        if (!in_array($semblable, $arr)) {
          array_push($arr, $semblable);
        }
      }
      //}
    }


    return response()->json($arr);
  }


  public function getListeAnomalies()
  {
    $equi_anomalies = equi_anomalie::where('exist','=',1)->get();
    return response()->json($equi_anomalies);
  }


  public function filterAnomalie(Request $request)
  {

    $form = $request->input('form');
    $searchFilterText = $form['searchFilterText'];
    $isStrict = true;
    $strict = $form['strict'];
    if ($strict == '0' || $strict == 0 || $strict === null) {
      $isStrict = false;
    }
    $nodes = $request->input('nodes');
    $arr = array();
    $precision = 60;

    if (count($nodes) == 0) {

      $equi_anomalies = equi_anomalie::where('equi_anomalies.exist', '=', 1)
        ->Where('equi_anomalies.anomalie', 'LIKE', '%' . $searchFilterText . '%')
        ->orderBy('equi_anomalies.anomalie', 'asc')
        ->get();

      foreach ($equi_anomalies as $equi_anomalie) {
        array_push($arr, $equi_anomalie);
      }

      if (!$isStrict) {
        $semblables = equi_anomalie::all();
        foreach ($semblables as $semblable) {
          $anomalie = $semblable->anomalie;
          if ($this->similar_text($anomalie, $searchFilterText, $precision) > $precision) {
            if (!in_array($semblable, $arr)) {
              array_push($arr, $semblable);
            }
          }
        }
      }

    } else {

      $equi_anomalies = collect(equi_anomalie::where('equi_anomalies.exist', '=', 1)
        ->join('equi_anomalies_equipements', function ($join) use ($nodes) {
          $join->on('equi_anomalies_equipements.anomalie_id', '=', 'equi_anomalies.AnomalieID')
            ->whereIn('equi_anomalies_equipements.equipement_id', $nodes);
        })
        ->distinct('equi_anomalies.AnomalieID')
        ->orderBy('equi_anomalies.anomalie', 'asc')
        ->get());

      $messagesUnique = $equi_anomalies->unique('AnomalieID');
      $equi_anomalies = $messagesUnique->values()->all();

      if ($searchFilterText) {
        $equi_anomalies = array_filter($equi_anomalies, function ($value) use ($searchFilterText, $isStrict, $precision) {
          if (stripos($value['anomalie'], $searchFilterText) !== FALSE) {
            return true;
          }
          if (!$isStrict) {
            if ($this->similar_text($value['anomalie'], $searchFilterText, $precision) > $precision) {
              return true;
            }
          }
        });
      }


      foreach ($equi_anomalies as $equi_anomalie) {
        array_push($arr, $equi_anomalie);
      }
    }


    return response()->json($arr);
  }




  public function FusionnerAnomalie(Request $request){
    $name=$request->input('name');
    $description=$request->input('description');
    //$description=$request->input('description');
    $anomalies=$request->input('anomalies');
    
   // return response()->json($anomalies);
    $arrEquipements=Array();
    foreach($anomalies as $anomalie){
     $AnomalieID=$anomalie['AnomalieID'];
     $equi_anomalies_equipements=equi_anomalies_equipement::where('anomalie_id','=',$AnomalieID)->get();
      foreach($equi_anomalies_equipements as $equi_anomalies_equipement){
        $EquipementID=$equi_anomalies_equipement->equipement_id;
        if (!in_array($EquipementID, $arrEquipements)) { array_push($arrEquipements, $EquipementID);}
      }
    }

    //$form = new \stdClass();
    //$form->anomalie = $name;
    //$form->description = "ayya description ... ";
    //$req = new Request(); $req->merge(['form' => $form]);
    //$this->addAnomalie($req,0);

    $form=['anomalie'=>$name,'description'=>$description,'exist'=>1];
    $Nvequi_anomalie = equi_anomalie::create($form);
      $NvAnomalieID = $Nvequi_anomalie->AnomalieID;
      if ($Nvequi_anomalie) {
        foreach ($arrEquipements as $NodeID) {
          equi_anomalies_equipement::create([
            'anomalie_id' => $NvAnomalieID,
            'equipement_id' => $NodeID
          ]);
        }
      }
      


      foreach($anomalies as $anomalie){
        $AnomalieID=$anomalie['AnomalieID'];
        // 9bal ma nfas5ou les anomalies 
        //na3mlou update lél tableawét lo5rin elli féhom EL id mte3 el anomalie kima demandeinterventions par exemple
        // probleme : 
        // on pose famma 7aja n9oulou DI 1 féha liste des anomalies
        // possible lista héthika féha zouz anomalies fusionner deja fi anomalie o5ra jdida
        // donc lazim yetfas5ou zouz w tet7at fi blaséthom anomalie fusionné
        // wa ella ma yetfas5ouch tetfassa5 wa7da w lo5ra tsirilha update mte3 ANomalieID 



        $equi_anomalies_equipements=equi_anomalies_equipement::where('anomalie_id','=',$AnomalieID)->delete();
        equi_anomalie::where('AnomalieID','=',$AnomalieID)->delete();

       }

     
    return response()->json($Nvequi_anomalie);

    }


    public function FusionnerTache(Request $request){
      $name=$request->input('name');
      $description=$request->input('description');
      //$description=$request->input('description');
      $taches=$request->input('taches');
      
     // return response()->json($anomalies);
      $arrEquipements=Array();
      foreach($taches as $tache){
       $TacheID=$tache['TacheID'];
       $equi_taches_equipements=equi_taches_equipement::where('tache_id','=',$TacheID)->get();
        foreach($equi_taches_equipements as $equi_taches_equipement){
          $EquipementID=$equi_taches_equipement->equipement_id;
          if (!in_array($EquipementID, $arrEquipements)) { array_push($arrEquipements, $EquipementID);}
        }
      }
  
      //$form = new \stdClass();
      //$form->anomalie = $name;
      //$form->description = "ayya description ... ";
      //$req = new Request(); $req->merge(['form' => $form]);
      //$this->addAnomalie($req,0);
  
      $form=['tache'=>$name,'description'=>$description,'exist'=>1];
      $Nvequi_tache = equi_tache::create($form);
        $NvTacheID = $Nvequi_tache->TacheID;
        if ($Nvequi_tache) {
          foreach ($arrEquipements as $NodeID) {
            equi_taches_equipement::create([
              'tache_id' => $NvTacheID,
              'equipement_id' => $NodeID
            ]);
          }
        }
        
  
  
        foreach($taches as $tache){
          $TacheID=$tache['TacheID'];
          // 9bal ma nfas5ou les anomalies 
          //na3mlou update lél tableawét lo5rin elli féhom EL id mte3 el anomalie kima demandeinterventions par exemple
          // probleme : 
          // on pose famma 7aja n9oulou DI 1 féha liste des anomalies
          // possible lista héthika féha zouz anomalies fusionner deja fi anomalie o5ra jdida
          // donc lazim yetfas5ou zouz w tet7at fi blaséthom anomalie fusionné
          // wa ella ma yetfas5ouch tetfassa5 wa7da w lo5ra tsirilha update mte3 ANomalieID 
  
  
  
          $equi_taches_equipements=equi_taches_equipement::where('tache_id','=',$TacheID)->delete();
          equi_tache::where('TacheID','=',$TacheID)->delete();
  
         }
  
       
      return response()->json($Nvequi_tache);
  
      }
  


    public function anomalieSearch(Request $request){
      $search = $request->input('search');
      return equi_anomalie::where('anomalie', 'Like', '%' . $search . '%')->get();
    }

    public function getAnomaliesByEquipementID($id){
     $equi_anomalies_equipements=equi_anomalies_equipement::where('equipement_id','=',$id)
     ->join('equi_anomalies', function ($join) {
      $join->on('equi_anomalies.AnomalieID', '=', 'equi_anomalies_equipements.anomalie_id')
      ->where('equi_anomalies.exist','=',1);
     })
     ->select('equi_anomalies.*')
     ->get();

     return response()->json($equi_anomalies_equipements);
    }

    public function getTachesByEquipementID($id){
      $equi_taches_equipements=equi_taches_equipement::where('equipement_id','=',$id)
      ->join('equi_taches', function ($join) {
       $join->on('equi_taches.TacheID', '=', 'equi_taches_equipements.tache_id')
       ->where('equi_taches.exist','=',1);
      })
      ->select('equi_taches.*')
      ->get();
 
      return response()->json($equi_taches_equipements);
    }


    public function getNiveauDetByEquipementID($id){
      $equi_equipement=equi_equipement::where('EquipementID','=',$id)->first();
      $BG=$equi_equipement->BG;
      $BD=$equi_equipement->BD;

      $equipement=$equi_equipement->equipement;
      
      $equi_equipements=equi_equipement::where('BG','<',$BG)->where('BD','>',$BD)->where('BG','>',1)
      ->where('exist','=',1)
      ->orderBy('equi_equipements.created_at', 'asc')
      ->get();

      return response()->json(['niveaus'=>$equi_equipements,'equipement'=>$equipement]);

    }

    public function getNiveausCompletDetByEquipementID($id){
      $equi_equipement=equi_equipement::where('EquipementID','=',$id)->first();
      $BG=$equi_equipement->BG;
      $BD=$equi_equipement->BD;
      
      $equi_equipements=equi_equipement::where('BG','<=',$BG)->where('BD','>=',$BD)->where('BG','>',1)
      ->where('exist','=',1)
      ->orderBy('equi_equipements.created_at', 'asc')
      ->get();

      return response()->json($equi_equipements);

    }



    public function getEquipementDeuxTest($id){
      $equipement2=equi_equipement::find($id)->equipementdeu;
      return response()->json($equipement2);
    }


    public function getAllDetEquipement($id){
      $equipement1=equi_equipement::find($id);
      $equipement2=equi_equipement::find($id)->equipementdeu;
      //$equipement = (object) array_merge((array) $equipement1, (array) $equipement2);
      $equipement=array_merge((array)json_decode($equipement1), (array) json_decode($equipement2));

      return response()->json(['equipement1'=>$equipement1,'equipement2'=>$equipement2,'equipement'=>$equipement]);
    }


    public function editEquipement(Request $request,$id){
      $form = $request->input('form');

      $equi_equipement=equi_equipement::where('EquipementID','=',$id)->update([
        'equipement'=>$form['equipement']
      ]);
      
      $equi_equipementdeu=equi_equipementdeu::where('EquipementID','=',$id)->update([
        'mise_en_service'=>$this->DateFormYMD($form['mise_en_service']),
        'isMarche'=>$form['isMarche'],
        'prixTTC'=>$form['prixTTC']
      ]);

      if($equi_equipement){
        return response()->json($form['equipement']);
      }else{
        return response()->json(false);
      }

    }

    public function getArticlesMagasinAtelierByEquipementID ($id){
      
      /*
          EquipementArticleID: null,
          equipement_id: this.data.EquipementID,
          article_id: 0,
          des: ''
      */

      /*
      $arr1=Array();

      $article1 = new \stdClass();
      $article1->EquipementArticleID =10;
      $article1->equipement_id =189;
      $article1->article_id =1;
      $article1->des ='_magasin1';

      $article2 = new \stdClass();
      $article2->EquipementArticleID =20;
      $article2->equipement_id =189;
      $article2->article_id =2;
      $article2->des ='_magasin2';

      array_push($arr1,$article1);
      array_push($arr1,$article2);

      $arr2=Array();

      $article1 = new \stdClass();
      $article1->EquipementArticleID =30;
      $article1->equipement_id =189;
      $article1->article_id =3;
      $article1->des ='_atelier1';

      $article2 = new \stdClass();
      $article2->EquipementArticleID =40;
      $article2->equipement_id =189;
      $article2->article_id =4;
      $article2->des ='_atelier2';

      array_push($arr2,$article1);
      array_push($arr2,$article2);
      
      return response()->json(['articlesMagasin'=>$arr1,'articlesAtelier'=>$arr2]);
      */


      $articlesMagasin=equi_magasin_article::where('equipement_id','=',$id)
      ->join('articles', function ($join) {
        $join->on('articles.ArticleID', '=', 'equi_magasin_articles.article_id');
       })
      ->select('equi_magasin_articles.*','articles.des')
      ->get();

      $articlesAtelier=equi_atelier_article::where('equipement_id','=',$id)
      ->join('atel_articles', function ($join) {
        $join->on('atel_articles.ArticleID', '=', 'equi_atelier_articles.article_id');
       })
      ->select('equi_atelier_articles.*','atel_articles.des')
      ->get();

      return response()->json([ 'articlesMagasin'=>$articlesMagasin , 'articlesAtelier'=>$articlesAtelier ]);

    } 


   public function AddOrEditEquipementArticle(Request $request,$id){

    $success=true;
    
   /*

    $articlesMagasin = $request->articlesMagasin;
    foreach ($articlesMagasin as $article) {
      $ArticleID = $article['article_id'];
      $equi_magasin_article = equi_magasin_article::create([
        'equipement_id' => $id,
        'article_id' => $ArticleID
      ]);

      if (!$equi_magasin_article) {
        $success = false;
      }
    }

    $articlesAtelier = $request->articlesAtelier;
    foreach ($articlesAtelier as $article) {
      $ArticleID = $article['article_id'];
      $equi_atelier_article = equi_atelier_article::create([
        'equipement_id' => $id,
        'article_id' => $ArticleID
      ]);

      if (!$equi_atelier_article) {
        $success = false;
      }
    }



    */


    $articlesMagasin = $request->articlesMagasin;
    $DeletedMagasinDetIDs = $request->DeletedMagasinDetIDs;
    foreach ($articlesMagasin as $article) {
      if ($article['EquipementArticleID'] == null) {
        $ArticleID = $article['article_id'];
        $equi_magasin_article = equi_magasin_article::create([
          'equipement_id' => $id,
          'article_id' => $ArticleID
        ]);
        if (!$equi_magasin_article) {
          $success = false;
        }
      }
    }
    $arrMagasinIDsToDelete = explode(',', $DeletedMagasinDetIDs, -1);
    foreach ($arrMagasinIDsToDelete as $ID) {
      $equi_magasin_article = equi_magasin_article::where('EquipementArticleID', '=', $ID)->delete();
      if (!$equi_magasin_article) {
        $success = false;
      }
    }


    $articlesAtelier = $request->articlesAtelier;
    $DeletedAtelierDetIDs = $request->DeletedAtelierDetIDs;
    foreach ($articlesAtelier as $article) {
      if ($article['EquipementArticleID'] == null) {
        $ArticleID = $article['article_id'];
        $equi_atelier_article = equi_atelier_article::create([
          'equipement_id' => $id,
          'article_id' => $ArticleID
        ]);
        if (!$equi_atelier_article) {
          $success = false;
        }
      }
    }
    $arrAtelierIDsToDelete = explode(',', $DeletedAtelierDetIDs, -1);
    foreach ($arrAtelierIDsToDelete as $ID) {
      $equi_atelier_article = equi_atelier_article::where('EquipementArticleID', '=', $ID)->delete();
      if (!$equi_atelier_article) {
        $success = false;
      }
    }

    return response()->json($success);


   }




/*
    SELECT *
FROM   NEW_FAMILLE
WHERE  NFM_BG < 29
   AND NFM_BD > 34
*/

    
  /*
 $table->Increments('DateTimeID');
            $table->date('date')->nullable();
            $table->Integer('heure')->nullable()->default(0);
            $table->Integer('minute')->nullable()->default(0);
            $table->time('time', 0)->nullable();;	
            $table->dateTime('datetime1')->nullable();
  */

  public function datetimetest(Request $request){
   $date=$request->input('date');
   $time=$request->input('time');

   $date=$this->DateFormYMD($date);
   $time=$this->TimeFormHM($time);
   
   $date1 = new DateTime($date);
   $time1 = new DateTime($time);
   $datetime1 = new DateTime($date1->format('Y-m-d') .' ' .$time1->format('H:i'));

   date_time_test::create(['date'=>$date,'time'=>$time,'datetime1'=>$datetime1]);
   

   return response()->json($date.','.$time);
  }

  public function affectDateTime($id){
    $res=date_time_test::where('DateTimeID','=',$id)->first();
    return response()->json($res);
  }
  
public function DateFormYMD($date){
  //$date->hour(5)->minute(5)->second(0); // $date->addDays(1);
  $date = Carbon::parse($date);
  $date= $date->format('Y-m-d');
  return $date;
}
public function TimeFormHM($time){
  //$date->hour(5)->minute(5)->second(0); // $date->addDays(1);
  $time = Carbon::parse($time);
  $time=$time->format('H:i');
  return $time;
}

  





  public function similar_text($first, $second, $percent)
  { // eslint-disable-line camelcase
    //  discuss at: http://locutus.io/php/similar_text/
    // original by: Rafał Kukawski (http://blog.kukawski.pl)
    // bugfixed by: Chris McMacken
    // bugfixed by: Jarkko Rantavuori original by findings in stackoverflow (http://stackoverflow.com/questions/14136349/how-does-similar-text-work)
    // improved by: Markus Padourek (taken from http://www.kevinhq.com/2012/06/php-similartext-function-in-javascript_16.html)
    //   example 1: similar_text('Hello World!', 'Hello locutus!')
    //   returns 1: 8
    //   example 2: similar_text('Hello World!', null)
    //   returns 2: 0

    if ($first === null || $second === null || gettype($first) === 'undefined' || gettype($second) === 'undefined') {
      return 0;
    };

    $first .= '';
    $second .= '';

    $pos1 = 0;
    $pos2 = 0;
    $max = 0;
    $firstLength = strlen($first);
    $secondLength = strlen($second);
    //$p;
    //$q;
    //$l;
    //$sum;

    for ($p = 0; $p < $firstLength; $p++) {
      for ($q = 0; $q < $secondLength; $q++) {
        for ($l = 0; ($p + $l < $firstLength) && ($q + $l < $secondLength) && ($first[$p + $l] === $second[$q + $l]); $l++) { // eslint-disable-line max-len
          // @todo: ^-- break up this crazy for loop and put the logic in its body
        }
        if ($l > $max) {
          $max = $l;
          $pos1 = $p;
          $pos2 = $q;
        }
      }
    }

    $sum = $max;

    if ($sum) {
      if ($pos1 && $pos2) {
        $sum += $this->similar_text(substr($first, 0, $pos1), substr($second, 0, $pos2), 0);
      }

      if (($pos1 + $max < $firstLength) && ($pos2 + $max < $secondLength)) {
        $sum += $this->similar_text(
          substr($first, $pos1 + $max, $firstLength - $pos1 - $max),
          substr($second, $pos2 + $max, $secondLength - $pos2 - $max),
          0
        );
      }
    }

    if (!$percent) {
      return $sum;
    }

    return ($sum * 200) / ($firstLength + $secondLength);
  }


}
