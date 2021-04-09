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












class RemplirController extends Controller
{
    //

    public function remplir(){
        
        art_famille::insert(['FamilleID'=>0,'famille'=>'Aucune']);
        art_famille::where('FamilleID','=',1)->update(['FamilleID'=>0]);


        art_fam_cara::insert(['FamCaraID'=>0,'name_famille'=>'Aucune','famille_id'=>0,'hasCara'=>0]);
        art_fam_cara::where('FamCaraID','=',1)->update(['FamCaraID'=>0]);


        art_fam_cara_det::insert(['FamCaraDetID'=>0,'name'=>'aucune','label'=>'aucune','fam_cara_id'=>0,'required'=>0,'type'=>'aucune','input'=>'aucune']);
        art_fam_cara_det::where('FamCaraDetID','=',1)->update(['FamCaraDetID'=>0]);
        
        art_emplacement::insert(['EmplacementID'=>0,'emplacement'=>'Aucune']);
        art_emplacement::where('EmplacementID','=',1)->update(['EmplacementID'=>0]);
        

        art_emplacement::create(['emplacement'=>'Emp 1']);
        art_emplacement::create(['emplacement'=>'Emp 2']);
        art_emplacement::create(['emplacement'=>'Emp 3']);
        // art_emplacement::insert(['EmplacementID'=>1,'emplacement'=>'Emp 1']);
        // art_emplacement::insert(['EmplacementID'=>2,'emplacement'=>'Emp 2']);
        // art_emplacement::insert(['EmplacementID'=>3,'emplacement'=>'Emp 3']);

        dossier::create(['nom'=>'DossierGenerale']);


        post::create(['post'=>'Admin','fonction'=>'Administrateur','des'=>'Administrateur']);
        post::create(['post'=>'ResponsableAchat','fonction'=>'Responsable Achat','des'=>'Responsable Achat']);
        post::create(['post'=>'ResponsableMagasin','fonction'=>'Responsable Magasin','des'=>'Responsable Magasin']);
        post::create(['post'=>'Methode','fonction'=>'Responsable Methode','des'=>'Responsable Methode']);
        post::create(['post'=>'ChefDeEquipe','fonction'=>'ChefDeEquipe','des'=>'ChefDeEquipe']);
        post::create(['post'=>'ChefDePoste','fonction'=>'ChefDePoste','des'=>'ChefDePoste']);
        post::create(['post'=>'ResponsableMaintenance','fonction'=>'ResponsableMaintenance','des'=>'ResponsableMaintenance']);
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

        User::create(['name'=>'ResponsableAchat1','prename'=>'ResponsableAchat1','age'=>33,
        'email'=>'ResponsableAchat1@ResponsableAchat1.com','password'=>bcrypt('ResponsableAchat1'),'poste'=>'ResponsableAchat1']);
        
        User::create(['name'=>'Admin','prename'=>'Admin','age'=>33,
        'email'=>'Admin@Admin.com','password'=>bcrypt('Admin'),'poste'=>'admin']);

        User::create(['name'=>'rien','prename'=>'rien','age'=>33,
        'email'=>'rien@rien.com','password'=>bcrypt('rien'),'poste'=>'rien']);

        User::create(['name'=>'ResponsableAchat2','prename'=>'ResponsableAchat2','age'=>33,
        'email'=>'ResponsableAchat2@ResponsableAchat2.com','password'=>bcrypt('ResponsableAchat2'),'poste'=>'ResponsableAchat2']);

        User::create(['name'=>'ChefDeEquipe1','prename'=>'ChefDeEquipe1','age'=>33,
        'email'=>'ChefDeEquipe1@ChefDeEquipe1.com','password'=>bcrypt('ChefDeEquipe1'),'poste'=>'ChefDeEquipe1']);

        User::create(['name'=>'ChefDeEquipe2','prename'=>'ChefDeEquipe2','age'=>33,
        'email'=>'ChefDeEquipe2@ChefDeEquipe2.com','password'=>bcrypt('ChefDeEquipe2'),'poste'=>'ChefDeEquipe2']);

        User::create(['name'=>'ChefDePoste1','prename'=>'ChefDePoste1','age'=>33,
        'email'=>'ChefDePoste1@ChefDePoste1.com','password'=>bcrypt('ChefDePoste1'),'poste'=>'ChefDePoste1']);

        User::create(['name'=>'ChefDePoste2','prename'=>'ChefDePoste2','age'=>33,
        'email'=>'ChefDePoste2@ChefDePoste2.com','password'=>bcrypt('ChefDePoste2'),'poste'=>'ChefDePoste2']);

        User::create(['name'=>'ResponsableMaintenance1','prename'=>'ResponsableMaintenance1','age'=>33,
        'email'=>'ResponsableMaintenance1@ResponsableMaintenance1.com','password'=>bcrypt('ResponsableMaintenance1'),'poste'=>'ResponsableMaintenance1']);

        User::create(['name'=>'ResponsableMaintenance2','prename'=>'ResponsableMaintenance2','age'=>33,
        'email'=>'ResponsableMaintenance2@ResponsableMaintenance2.com','password'=>bcrypt('ResponsableMaintenance2'),'poste'=>'ResponsableMaintenance2']);

        User::create(['name'=>'ResponsableMethode','prename'=>'ResponsableMethode','age'=>33,
        'email'=>'ResponsableMethode@ResponsableMethode.com','password'=>bcrypt('ResponsableMethode'),'poste'=>'ResponsableMethode']);


        User::create(['name'=>'ResponsableMagasin1','prename'=>'ResponsableMagasin1','age'=>33,
        'email'=>'ResponsableMagasin1@ResponsableMagasin1.com','password'=>bcrypt('ResponsableMagasin1'),'poste'=>'ResponsableMagasin1']);

        User::create(['name'=>'ResponsableMagasin2','prename'=>'ResponsableMagasin2','age'=>33,
        'email'=>'ResponsableMagasin2@ResponsableMagasin2.com','password'=>bcrypt('ResponsableMagasin2'),'poste'=>'ResponsableMagasin2']);



        user_post::create(['user_id'=>2,'post_id'=>1]);
        user_post::create(['user_id'=>1,'post_id'=>2]);
        user_post::create(['user_id'=>4,'post_id'=>2]);
        user_post::create(['user_id'=>5,'post_id'=>5]);
        user_post::create(['user_id'=>6,'post_id'=>5]);
        user_post::create(['user_id'=>7,'post_id'=>6]);
        user_post::create(['user_id'=>8,'post_id'=>6]);
        user_post::create(['user_id'=>11,'post_id'=>4]);
        user_post::create(['user_id'=>9,'post_id'=>7]);
        user_post::create(['user_id'=>10,'post_id'=>7]);
        user_post::create(['user_id'=>12,'post_id'=>3]);
        user_post::create(['user_id'=>13,'post_id'=>3]);

        
        art_etat::create(['etat'=>'Faible','exist'=>1]);
        art_etat::create(['etat'=>'Moyen','exist'=>1]);
        art_etat::create(['etat'=>'Bien','exist'=>1]);

        art_secteur::create(['secteur'=>'Secteur1','exist'=>1]);
        art_secteur::create(['secteur'=>'Secteur2','exist'=>1]);
        art_secteur::create(['secteur'=>'Secteur3','exist'=>1]);

        secteur::create(['secteur'=>'Secteur1']);
        secteur::create(['secteur'=>'Secteur2']);
        secteur::create(['secteur'=>'Secteur3']);

        art_type::create(['type'=>'Type1','exist'=>1]);
        art_type::create(['type'=>'Type2','exist'=>1]);

        art_utilisation::create(['utilisation'=>'Utilisation1','exist'=>1]);
        art_utilisation::create(['utilisation'=>'Utilisation2','exist'=>1]);
        art_utilisation::create(['utilisation'=>'Utilisation3','exist'=>1]);
        art_utilisation::create(['utilisation'=>'Utilisation4','exist'=>1]);
        art_utilisation::create(['utilisation'=>'Utilisation5','exist'=>1]);

        equi_niveau::create(['niveau'=>0,'nom'=>'Racine','isMin'=>0,'isMax'=>0,'exist'=>1]);
        equi_niveau::create(['niveau'=>1,'nom'=>'Unite','isMin'=>0,'isMax'=>0,'exist'=>1]);
        equi_niveau::create(['niveau'=>2,'nom'=>'Ligne','isMin'=>0,'isMax'=>0,'exist'=>1]);
        equi_niveau::create(['niveau'=>3,'nom'=>'Equipement','isMin'=>1,'isMax'=>0,'exist'=>1]);
        equi_niveau::create(['niveau'=>4,'nom'=>'SousEquipemnt','isMin'=>0,'isMax'=>1,'exist'=>1]);


        equi_equipement::create(['BG'=>1,'BD'=>2,'Niv'=>0,'niveau_id'=>1,'equipement'=>'Arbre Equipement','exist'=>1]);

        

      

        return response()->json(true);


    }
}
