<?php

namespace App\Http\Controllers\Divers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\Divers\dossier;
use App\Model\Divers\file;

class upload extends Controller
{

    //
    public function upload(Request $request,$id){
       // return response()->json("suc");
        //1021155836
        //$originalRequest=$request;
        /*
        $table->Increments('FileID');
        $table->Integer('dossier_id')->unsigned();
        $table->string('type',50)->nullable(); // passport // image ... // fichier 
        $table->string('OriginaleName',100);
        $table->string('BDName',100);
        $table->string('size',50)->nullable();
        $table->string('extention',20)->nullable();
        */
        if($id>1){
        if ($request->hasFile('document'))
        {
              $file      = $request->file('document');
              $fileOrihginale=$file;
              $filename  = $file->getClientOriginalName();
              $OriginaleName=$filename;
              $extension = $file->getClientOriginalExtension();
              $randompicture=rand(100000,999999);
              $picture   = $randompicture.date('His').'.'.$extension;//.'-'.$filename;
              $saved=$file->move(public_path('/files/fichiers'), $picture);
              if($saved){
                   
                  $file=new file(); 
                  $file->dossier_id=$id;
                  $file->OriginaleName=$OriginaleName;
                  $file->BDName=$picture;
                  $file->size=$request->input('size');
                  $file->extention=$extension;
                  $file->save();
                  
                  $FileID=$file->FileID;
                  return response()->json($FileID);
                  }
        }
    }
        return response()->json('error');
     }

     public function creerundossier(Request $request){
         $dosseir=dossier::create($request->all());
         $DossierID=$dosseir->DossierID;
         if($DossierID) return response()->json($DossierID);
         else  return response()->json('0');
     }

     public function LocalCreerundossier($nom,$type,$des,$lien){
        $dosseir=dossier::create(['nom'=>$nom,'type'=>$type,'des'=>$des,'lien'=>$lien]);
        $DossierID=$dosseir->DossierID;
        if($DossierID) return $DossierID;
        else  return 0;
    }

    
    public function remove($id){
      $retour= file::find($id)->delete();
      return response()->json($retour);
    }


    public function getfiles($id){
        $dossier=dossier::find($id);
        $DossierID=$dossier->DossierID;
        $files=file::where('dossier_id','=',$DossierID)->get();
        return response()->json($files);
      }



////////////////////////////// nz uplaod test 
/*
public function uploadOneFileNZ(Request $request,$id){
 
     if($id<=1){
        $id=$this->LocalCreerundossier('nommm','typeee','desss','liennn');
     }

     if($id!=0){
     if ($request->hasFile('document'))
     {
           $file      = $request->file('document');
           $fileOrihginale=$file;
           $filename  = $file->getClientOriginalName();
           $OriginaleName=$filename;
           $extension = $file->getClientOriginalExtension();
           $randompicture=rand(100000,999999);
           $picture   = $randompicture.date('His').'.'.$extension;//.'-'.$filename;
           $saved=$file->move(public_path('/files/fichiers'), $picture);
            


           if($saved){
                
               $status=true;
               $url="http://localhost:8000/files/fichiers/".$picture;

               $file=new file(); 
               $file->dossier_id=$id;
               $file->OriginaleName=$OriginaleName;
               $file->BDName=$picture;
               $file->size=$request->input('size');
               $file->extention=$extension;
               $file->save();
               $FileID=$file->FileID;
               //return response()->json($FileID);

               $file=new file();
               $file->uid=$FileID;
               $file->name=$OriginaleName;
               $file->bdname=$picture;
               $file->status=$status;
               $file->url=$url;
               $file->thumbUrl=$url;

               return response()->json(['file'=>$file,'uid'=>$FileID,'dossier_id'=>$id,'name'=>$OriginaleName,'bdname'=>$picture,'status'=>$status,'url'=>$url,'thumbUrl'=>$url]);
               

               }else{

               $status=false;
               $url="http://localhost:8000/files/fichiers/207905060741.jpg";

               $file=new file(); 
               $file->dossier_id=$id;
               $file->OriginaleName=$OriginaleName;
               $file->BDName=$picture;
               $file->size=$request->input('size');
               $file->extention=$extension;
               $file->save();
               $FileID=$file->FileID;
               //return response()->json($FileID);

               $file=new file();
               $file->uid=$FileID;
               $file->name=$OriginaleName;
               $file->bdname=$picture;
               $file->status=$status;
               $file->url=$url;
               $file->thumbUrl=$url;
               $file->dossier_id=$id;
               

               return response()->json(['file'=>$file,'uid'=>$FileID,'name'=>$OriginaleName,'bdname'=>$picture,'status'=>$status,'url'=>$url,'thumbUrl'=>$url,'dossier_id'=>$id]);
              

               }
     }else{
        return response()->json(['status'=>false]); 
     }
    }else{
        return response()->json(['status'=>false]); 
    }
 



}
*/

}

