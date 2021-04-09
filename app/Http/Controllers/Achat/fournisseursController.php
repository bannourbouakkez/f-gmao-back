<?php

namespace App\Http\Controllers\Achat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\User;

use App\Model\Achat\fournisseur;
use App\Model\Achat\secteur;
use App\Model\Achat\livmode;
use App\Model\Divers\file;
use App\Model\Divers\dossier;

class fournisseursController extends Controller
{

    public function index(){
        return fournisseur::all();  
      }

      public function indextestfiltersideserver(Request $request ){
        $search=$request->input('search');
        return fournisseur::where('nom','Like','%'.$search.'%')->get();
      }


    public function show($id){
        return fournisseur::find($id);
      }

    public function addoredit(Request $request){
     
      //===> test uplaod 
      //hasFile('fichier')
     
     // return response()->json($request->all());
     $originalRequest=$request;
     $idf=$request->input('id');
     /*
      if ($request->hasFile('fichier'))
      {
            $file      = $request->file('fichier');
            $filename  = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $randompicture=rand(1,10000);
            $picture   = $randompicture.date('His').'-'.$filename;
            $file->move(public_path('/files/fichiers'), $picture);
            // if($file) // delete le9dima 
            $request=$request->except('fichier');
            $request=(object)$request;
            $request->fichier=$picture;
          
      }else{
        $request=$request->except('fichier');
        $request=(object)$request;
        $request->fichier="";
      }
      */
      //====> upload next 
      $request=$request->all();
      //$request=(object)$request;
      $request = (array) $request;

      if($idf){
        $retour=fournisseur::where('id', $idf)->update($request);
        return response()->json($retour);
      }else{
        $random=rand(1,1000000);
        $request=(object)$request; $request->ref=$random;$request = (array) $request;
        //$request->merge(['ref' => $random]);
        $idf=fournisseur::insertGetId($request);
        $retour=fournisseur::where('id', $idf)->update(array('ref' => ''.$idf.''));
        return response()->json($retour);
      }
    }

    //==========================================> Secteurs 
    public function indexsecteur(){
      return secteur::all();  
    }

    public function showsecteur($id){
      return secteur::find($id);
    }

    public function showfiles($id){
      $dossier=dossier::find($id);
      $DossierID=$dossier->DossierID;
      $files=file::where('dossier_id','=',$DossierID)->get();
      return response()->json($files);
    }


    public function addoreditsecteur(Request $request){
      $ids=$request->input('id');
      if($ids){
        secteur::where('id', $ids)->update($request->all());
      }else{
        $retour=secteur::create($request->all());
        return response()->json($retour);
      }
    }

    public function deletesecteur($id){
      $retour= secteur::find($id)->delete();
      return response()->json($retour);
    }

    //==========================================> Modes 
    public function indexmode(){
      return livmode::all();  
    }

    public function showmode($id){
      return livmode::find($id);
    }

    public function addoreditmode(Request $request){
      $ids=$request->input('id');
      if($ids){
        livmode::where('id', $ids)->update($request->all());
      }else{
        $retour=livmode::create($request->all());
        return response()->json($retour);
      }
    }

    public function deletemode($id){
      $retour= livmode::find($id)->delete();
      return response()->json($retour);
    }
  


    //_________________

    public function getUsers(Request $request){
        return user::all();
    }
}
