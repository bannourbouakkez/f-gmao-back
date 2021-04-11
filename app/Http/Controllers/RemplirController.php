<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Model\Magasin\art_fam_cara;
use App\Model\Magasin\art_fam_cara_det;
use App\Model\Magasin\art_famille;
use App\Model\Magasin\art_emplacement;
use App\Model\Divers\dossier;
use App\Model\Auth\post;
use App\Model\Divers\unite;
use App\User;
use App\Model\Auth\user_post;
use App\Model\Magasin\art_etat;
use App\Model\Magasin\art_secteur;
use App\Model\Achat\secteur;
use App\Model\Magasin\art_type;
use App\Model\Magasin\art_utilisation;
use App\Model\Equipement\equi_niveau;
use App\Model\Equipement\equi_equipement;
use App\Model\Achat\fournisseur;
use App\Model\Achat\livmode;











class RemplirController extends Controller
{
    //

    public function remplir(){
        
        $DossierID=dossier::create(['nom'=>'DossierGenerale'])->DossierID;
        dossier::where('DossierID','=',$DossierID)->update(['DossierID'=>1]);
        

        $FamilleID=art_famille::create(['famille'=>'Aucune'])->FamilleID;
        art_famille::where('FamilleID','=',$FamilleID)->update(['FamilleID'=>0]);

        $livemodeid1=livmode::create(['mode'=>'Mode Paiement 1'])->id;
        $livemodeid2=livmode::create(['mode'=>'Mode Paiement 2'])->id;
        $livemodeid3=livmode::create(['mode'=>'Mode Paiement 3'])->id;

        $SecteurID1=art_secteur::create(['secteur'=>'Secteur1','exist'=>1])->SecteurID;
        $SecteurID2=art_secteur::create(['secteur'=>'Secteur2','exist'=>1])->SecteurID;
        $SecteurID3=art_secteur::create(['secteur'=>'Secteur3','exist'=>1])->SecteurID;

        secteur::create(['secteur'=>'Secteur1']);
        secteur::create(['secteur'=>'Secteur2']);
        secteur::create(['secteur'=>'Secteur3']);

        art_famille::create(['famille'=>'Moteur']);
        art_famille::create(['famille'=>'Filtre']);
        art_famille::create(['famille'=>'Compresseur']);
        art_famille::create(['famille'=>'Thermostat']);
        art_famille::create(['famille'=>'Batterie']);

        // $emplacements=livmode::all();
        // return response()->json($emplacements);

        fournisseur::create(['nom'=>'Mohammed Ben Frej','livmode_id'=>$livemodeid1,'secteur_id'=>$SecteurID1,'dossier_id'=>1,'ref'=>1,'TVA'=>18,'tel1'=>1111111]);
        fournisseur::create(['nom'=>'Amir Haj Ahmed','livmode_id'=>$livemodeid2,'secteur_id'=>$SecteurID2,'dossier_id'=>1,'ref'=>2,'TVA'=>18,'tel1'=>22222222]);
        fournisseur::create(['nom'=>'ALi Doss','livmode_id'=>$livemodeid2,'secteur_id'=>$SecteurID1,'dossier_id'=>1,'ref'=>3,'TVA'=>25,'tel1'=>33333333]);
        fournisseur::create(['nom'=>'Foued Mabrouk','livmode_id'=>$livemodeid1,'secteur_id'=>$SecteurID2,'dossier_id'=>1,'ref'=>4,'TVA'=>10,'tel1'=>44444444]);
        fournisseur::create(['nom'=>'Sami Salah','livmode_id'=>$livemodeid3,'secteur_id'=>$SecteurID3,'dossier_id'=>1,'ref'=>5,'TVA'=>18,'tel1'=>55555555]);

        $FamCaraID=art_fam_cara::create(['name_famille'=>'Aucune','famille_id'=>0,'hasCara'=>0])->FamCaraID;
        art_fam_cara::where('FamCaraID','=',$FamCaraID)->update(['FamCaraID'=>0]);


        $FamCaraDetID=art_fam_cara_det::create(['name'=>'aucune','label'=>'aucune','fam_cara_id'=>0,'required'=>0,'type'=>'aucune','input'=>'aucune'])->FamCaraDetID;
        art_fam_cara_det::where('FamCaraDetID','=',$FamCaraDetID)->update(['FamCaraDetID'=>0]);
        
        $EmplacementID=art_emplacement::create(['emplacement'=>'Aucune'])->EmplacementID;
        art_emplacement::where('EmplacementID','=',$EmplacementID)->update(['EmplacementID'=>0]);
        //art_emplacement::create(['emplacement'=>'Aucune']);
        //art_emplacement::where('EmplacementID','=',1)->update(['EmplacementID'=>0]);
        art_emplacement::create(['emplacement'=>'Emplacement 1']);
        art_emplacement::create(['emplacement'=>'Emplacement 2']);
        art_emplacement::create(['emplacement'=>'Emplacement 3']);
        // art_emplacement::insert(['EmplacementID'=>1,'emplacement'=>'Emp 1']);
        // art_emplacement::insert(['EmplacementID'=>2,'emplacement'=>'Emp 2']);
        // art_emplacement::insert(['EmplacementID'=>3,'emplacement'=>'Emp 3']);



        $PostIDAdmin=post::create(['post'=>'Admin','fonction'=>'Administrateur','des'=>'Administrateur'])->PostID;
        $PostIDResponsableAchat=post::create(['post'=>'ResponsableAchat','fonction'=>'Responsable Achat','des'=>'Responsable Achat'])->PostID;
        $PostIDResponsableMagasin=post::create(['post'=>'ResponsableMagasin','fonction'=>'Responsable Magasin','des'=>'Responsable Magasin'])->PostID;
        $PostIDMethode=post::create(['post'=>'Methode','fonction'=>'Responsable Methode','des'=>'Responsable Methode'])->PostID;
        $PostIDChefDeEquipe=post::create(['post'=>'ChefDeEquipe','fonction'=>'ChefDeEquipe','des'=>'ChefDeEquipe'])->PostID;
        $PostIDChefDePoste=post::create(['post'=>'ChefDePoste','fonction'=>'ChefDePoste','des'=>'ChefDePoste'])->PostID;
        $PostIDResponsableMaintenance=post::create(['post'=>'ResponsableMaintenance','fonction'=>'ResponsableMaintenance','des'=>'ResponsableMaintenance'])->PostID;
        
        // post::insert(['PostID'=>1,'post'=>'Admin','fonction'=>'Administrateur','des'=>'Administrateur']);
        // post::insert(['PostID'=>2,'post'=>'ResponsableAchat','fonction'=>'Responsable Achat','des'=>'Responsable Achat']);
        // post::insert(['PostID'=>3,'post'=>'ResponsableMagasin','fonction'=>'Responsable Magasin','des'=>'Responsable Magasin']);
        // post::insert(['PostID'=>4,'post'=>'Methode','fonction'=>'Responsable Methode','des'=>'Responsable Methode']);
        // post::insert(['PostID'=>5,'post'=>'ChefDeEquipe','fonction'=>'ChefDeEquipe','des'=>'ChefDeEquipe']);
        // post::insert(['PostID'=>6,'post'=>'ChefDePoste','fonction'=>'ChefDePoste','des'=>'ChefDePoste']);
        // post::insert(['PostID'=>7,'post'=>'Responsable Maintenance','fonction'=>'ResponsableMaintenance','des'=>'ResponsableMaintenance']);


        unite::create(['unite'=>'unitaire','TypeUnite'=>'integer','exist'=>1]);
        unite::create(['unite'=>'Kg','TypeUnite'=>'float','exist'=>1]);
        unite::create(['unite'=>'M²','TypeUnite'=>'float','exist'=>1]);
        unite::create(['unite'=>'Litre','TypeUnite'=>'float','exist'=>1]);
        unite::create(['unite'=>'V²','TypeUnite'=>'integer','exist'=>1]);
        // unite::insert(['UniteID'=>1,'unite'=>'unitaire','TypeUnite'=>'integer','exist'=>1]);
        // unite::insert(['UniteID'=>2,'unite'=>'Kg','TypeUnite'=>'float','exist'=>1]);
        // unite::insert(['UniteID'=>2,'unite'=>'M²','TypeUnite'=>'float','exist'=>1]);
        // unite::insert(['UniteID'=>4,'unite'=>'Litre','TypeUnite'=>'float','exist'=>1]);
        // unite::insert(['UniteID'=>5,'unite'=>'V²','TypeUnite'=>'integer','exist'=>1]);

        $UserID1=User::create(['name'=>'ResponsableAchat1','prename'=>'ResponsableAchat1','age'=>33,
        'email'=>'ResponsableAchat1@ResponsableAchat1.com','password'=>bcrypt('ResponsableAchat1'),'poste'=>'ResponsableAchat'])->id;
        
        $UserID2=User::create(['name'=>'Admin','prename'=>'Admin','age'=>33,
        'email'=>'Admin@Admin.com','password'=>bcrypt('Admin'),'poste'=>'admin'])->id;

        $UserID3=User::create(['name'=>'rien','prename'=>'rien','age'=>33,
        'email'=>'rien@rien.com','password'=>bcrypt('rien'),'poste'=>'rien'])->id;

        $UserID4=User::create(['name'=>'ResponsableAchat2','prename'=>'ResponsableAchat2','age'=>33,
        'email'=>'ResponsableAchat2@ResponsableAchat2.com','password'=>bcrypt('ResponsableAchat2'),'poste'=>'ResponsableAchat'])->id;

        $UserID5=User::create(['name'=>'ChefDeEquipe1','prename'=>'ChefDeEquipe1','age'=>33,
        'email'=>'ChefDeEquipe1@ChefDeEquipe1.com','password'=>bcrypt('ChefDeEquipe1'),'poste'=>'ChefDeEquipe'])->id;

        $UserID6=User::create(['name'=>'ChefDeEquipe2','prename'=>'ChefDeEquipe2','age'=>33,
        'email'=>'ChefDeEquipe2@ChefDeEquipe2.com','password'=>bcrypt('ChefDeEquipe2'),'poste'=>'ChefDeEquipe'])->id;

        $UserID7=User::create(['name'=>'ChefDePoste1','prename'=>'ChefDePoste1','age'=>33,
        'email'=>'ChefDePoste1@ChefDePoste1.com','password'=>bcrypt('ChefDePoste1'),'poste'=>'ChefDePoste'])->id;

        $UserID8=User::create(['name'=>'ChefDePoste2','prename'=>'ChefDePoste2','age'=>33,
        'email'=>'ChefDePoste2@ChefDePoste2.com','password'=>bcrypt('ChefDePoste2'),'poste'=>'ChefDePoste'])->id;

        $UserID9=User::create(['name'=>'ResponsableMaintenance1','prename'=>'ResponsableMaintenance1','age'=>33,
        'email'=>'ResponsableMaintenance1@ResponsableMaintenance1.com','password'=>bcrypt('ResponsableMaintenance1'),'poste'=>'ResponsableMaintenance'])->id;

        $UserID10=User::create(['name'=>'ResponsableMaintenance2','prename'=>'ResponsableMaintenance2','age'=>33,
        'email'=>'ResponsableMaintenance2@ResponsableMaintenance2.com','password'=>bcrypt('ResponsableMaintenance2'),'poste'=>'ResponsableMaintenance'])->id;

        $UserID11=User::create(['name'=>'ResponsableMethode','prename'=>'ResponsableMethode','age'=>33,
        'email'=>'ResponsableMethode@ResponsableMethode.com','password'=>bcrypt('ResponsableMethode'),'poste'=>'Methode'])->id;


        $UserID12=User::create(['name'=>'ResponsableMagasin1','prename'=>'ResponsableMagasin1','age'=>33,
        'email'=>'ResponsableMagasin1@ResponsableMagasin1.com','password'=>bcrypt('ResponsableMagasin1'),'poste'=>'ResponsableMagasin'])->id;

        $UserID13=User::create(['name'=>'ResponsableMagasin2','prename'=>'ResponsableMagasin2','age'=>33,
        'email'=>'ResponsableMagasin2@ResponsableMagasin2.com','password'=>bcrypt('ResponsableMagasin2'),'poste'=>'ResponsableMagasin'])->id;



        user_post::create(['user_id'=>$UserID2,'post_id'=>$PostIDAdmin]);
        user_post::create(['user_id'=>$UserID1,'post_id'=>$PostIDResponsableAchat]);
        user_post::create(['user_id'=>$UserID4,'post_id'=>$PostIDResponsableAchat]);
        user_post::create(['user_id'=>$UserID5,'post_id'=>$PostIDChefDeEquipe]);
        user_post::create(['user_id'=>$UserID6,'post_id'=>$PostIDChefDeEquipe]);
        user_post::create(['user_id'=>$UserID7,'post_id'=>$PostIDChefDePoste]);
        user_post::create(['user_id'=>$UserID8,'post_id'=>$PostIDChefDePoste]);
        user_post::create(['user_id'=>$UserID11,'post_id'=>$PostIDMethode]);
        user_post::create(['user_id'=>$UserID9,'post_id'=>$PostIDResponsableMaintenance]);
        user_post::create(['user_id'=>$UserID10,'post_id'=>$PostIDResponsableMaintenance]);
        user_post::create(['user_id'=>$UserID12,'post_id'=>$PostIDResponsableMagasin]);
        user_post::create(['user_id'=>$UserID13,'post_id'=>$PostIDResponsableMagasin]);

        
        art_etat::create(['etat'=>'Faible','exist'=>1]);
        art_etat::create(['etat'=>'Moyen','exist'=>1]);
        art_etat::create(['etat'=>'Bien','exist'=>1]);

        

        art_type::create(['type'=>'Type1','exist'=>1]);
        art_type::create(['type'=>'Type2','exist'=>1]);

        art_utilisation::create(['utilisation'=>'Utilisation1','exist'=>1]);
        art_utilisation::create(['utilisation'=>'Utilisation2','exist'=>1]);
        art_utilisation::create(['utilisation'=>'Utilisation3','exist'=>1]);
        art_utilisation::create(['utilisation'=>'Utilisation4','exist'=>1]);
        art_utilisation::create(['utilisation'=>'Utilisation5','exist'=>1]);

        $NiveauID1=equi_niveau::create(['niveau'=>0,'nom'=>'Racine','isMin'=>0,'isMax'=>0,'exist'=>1])->NiveauID;
        equi_niveau::create(['niveau'=>1,'nom'=>'Unite','isMin'=>0,'isMax'=>0,'exist'=>1]);
        equi_niveau::create(['niveau'=>2,'nom'=>'Ligne','isMin'=>0,'isMax'=>0,'exist'=>1]);
        equi_niveau::create(['niveau'=>3,'nom'=>'Equipement','isMin'=>1,'isMax'=>0,'exist'=>1]);
        equi_niveau::create(['niveau'=>4,'nom'=>'SousEquipemnt','isMin'=>0,'isMax'=>1,'exist'=>1]);


        equi_equipement::create(['BG'=>1,'BD'=>2,'Niv'=>0,'niveau_id'=>$NiveauID1,'equipement'=>'Arbre Equipement','exist'=>1]);

        

      
        // $emplacements=livmode::all();
        // return response()->json($emplacements);

        return response()->json(true);
        


    }

    public function getallequiepemnttest()
  {

    $equi_equipement = equi_equipement::all();
    return response()->json($equi_equipement);


  }

  public function getChildstest(Request $request, $id)
  {

    $all = $request->input('all');
    $isAll = true;
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
  
}
