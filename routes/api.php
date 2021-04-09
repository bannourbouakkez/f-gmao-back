<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//Route::middleware('auth:api')->get('/user', function (Request $request) { return $request->user(); });
//Route::middleware('jwt.auth')->get('/users', function (Request $request) { return auth()->user(); });

Route::group([
    'prefix' => 'config'
], function ($router) {
    Route::get('remplir', 'RemplirController@remplir');
});

Route::group([
    //'middleware' => 'jwt.auth',
    'prefix' => 'files'
], function ($router) {
    Route::post('creerundossier', 'Divers\upload@creerundossier');

    Route::post('upload/{id}', 'Divers\upload@upload');
    Route::delete('remove/{id}', 'Divers\upload@remove');
    Route::get('getfiles/{id}', 'Divers\upload@getfiles');


    //Route::post('uploadOneFileNZ/{id}', 'Divers\upload@uploadOneFileNZ');

    
    
});


Route::group([
    //'middleware' => 'jwt.auth',
    'prefix' => 'auth'
], function ($router) {
    Route::post('login', 'AuthController@login');
    Route::post('logout', 'AuthController@logout');
    Route::post('refresh', 'AuthController@refresh');
    Route::post('me', 'AuthController@me');
    
    Route::post('registerTemporelle', 'AuthController@registerTemporelle');
});


Route::group([
    'middleware' => 'jwt.auth', 

    'prefix' => 'generale'
], function ($router) {
    Route::post('getUsersPosts', 'Divers\generale@getUsersPosts');
    Route::get('getIntervenants', 'Divers\generale@getIntervenants');
    Route::post('getOutilsBySearchWord', 'Divers\generale@getOutilsBySearchWord');
    Route::post('getIntervenantsBySearchWord', 'Divers\generale@getIntervenantsBySearchWord');
    Route::post('getTachesBySearchWord', 'Divers\generale@getTachesBySearchWord');
    Route::post('getTachesBySearchWordByEquipementID', 'Divers\generale@getTachesBySearchWordByEquipementID');
    Route::post('getTachesBySearchWordByInterventionID', 'Divers\generale@getTachesBySearchWordByInterventionID');
    
    Route::post('ModificationDate', 'Divers\generale@ModificationDate');
    
});

Route::group([
    //'middleware' => 'jwt.auth',
   
    'prefix' => 'achat'
], function ($router) {
    Route::get('fournisseurs', 'Achat\fournisseursController@index');
    Route::get('fournisseur/{id}', 'Achat\fournisseursController@show');
    Route::post('fournisseurs', 'Achat\fournisseursController@addoredit');
    Route::post('fournisseurs/testfiltersideserver', 'Achat\fournisseursController@indextestfiltersideserver');
    Route::post('fournisseurs/{id}', 'Achat\fournisseursController@delete');
    

    Route::get('secteurs', 'Achat\fournisseursController@indexsecteur');
    Route::get('secteurs/{id}', 'Achat\fournisseursController@showsecteur');
    Route::post('secteurs', 'Achat\fournisseursController@addoreditsecteur');
    Route::delete('secteurs/{id}', 'Achat\fournisseursController@deletesecteur');

    Route::get('modes', 'Achat\fournisseursController@indexmode');
    Route::get('modes/{id}', 'Achat\fournisseursController@showmode');
    Route::post('modes', 'Achat\fournisseursController@addoreditmode');
    Route::delete('modes/{id}', 'Achat\fournisseursController@deletemode');

    //Route::post('articles', 'Magasin\article\articlesController@indexsearch');
    //Route::get('articles/{id}', 'Magasin\article\articlesController@show');

    Route::get('fournisseurs/files/{id}', 'Achat\fournisseursController@showfiles');
    
});


Route::group([
    'middleware' => 'jwt.auth',
    'prefix' => 'achat/da'
], function ($router) {
    Route::get('das', 'Achat\da\daController@indexda');
    Route::get('das/{id}', 'Achat\da\daController@showda');
    Route::post('addda', 'Achat\da\daController@addda');
    Route::post('updateda', 'Achat\da\daController@updateda');

    // Nv ba3ad ma kammalt module magasin w rja3t bil 5ebra
    Route::get('nvGetDa/{id}', 'Achat\da\daController@nvGetDa');
    
    //###############
    
});

Route::group([
    'middleware' => 'jwt.auth',
    'prefix' => 'achat/gererda'
  
], function ($router) {

    Route::get('das', 'Achat\da\daController@indexgererda');
    Route::get('das/{statut}', 'Achat\da\daController@indexgererdawithstatut');
    Route::get('da/{id}', 'Achat\da\daController@showgererda');

    Route::get('action/checkreporte', 'Achat\da\actionController@checkDasReportees');

    Route::post('action/admin/{id}', 'Achat\da\actionController@actionadmin');
    Route::post('action/responsableachat/{id}', 'Achat\da\actionController@actionresponsableachat');
    Route::post('action/utilisateur/{id}', 'Achat\da\actionController@actionutilisateur');

    Route::post('filter', 'Achat\da\daController@filter');

});

Route::group([
    'middleware' => 'jwt.auth', 
    'prefix' => 'achat/cmd'
], function ($router) {
    Route::get('fournisseurscmde', 'Achat\cmd\cmdController@getfournisseurcmde');
    Route::post('articlescmde/{id}', 'Achat\cmd\cmdController@getarticlecmde');
    Route::post('add', 'Achat\cmd\cmdController@addcmd');
    Route::get('liste', 'Achat\cmd\cmdController@getlistecmd');
    Route::get('cmd/{id}', 'Achat\cmd\cmdController@getcmd');
    Route::get('corbeille', 'Achat\cmd\cmdController@cmdcorbeille');
    Route::delete('cmd/{id}', 'Achat\cmd\cmdController@deletecmd');
    Route::get('restaurer/{id}', 'Achat\cmd\cmdController@restaurercmd');
    Route::post('nvreception', 'Achat\cmd\cmdController@nvreception');
    Route::get('cmdreceptions/{id}', 'Achat\cmd\cmdController@cmdreceptions');
    Route::get('reception/{id}', 'Achat\cmd\cmdController@reception');
    Route::post('editreception/{id}', 'Achat\cmd\cmdController@editreception');
    Route::get('recmodifs/{id}', 'Achat\cmd\cmdController@recmodifs');
    Route::get('modif/{id}', 'Achat\cmd\cmdController@getmodif');
    Route::get('fermercmd/{id}', 'Achat\cmd\cmdController@fermercmd');
    Route::get('ouvrircmd/{id}', 'Achat\cmd\cmdController@ouvrircmd');
    Route::get('droitrestaurationcmd/{id}', 'Achat\cmd\cmdController@droitrestaurationcmd');
    Route::get('fournisseurcmds/{id}', 'Achat\cmd\cmdController@fournisseurcmds');
    Route::post('imprimer', 'Achat\cmd\cmdController@imprimer');
    Route::post('cmdfilter/{exist}', 'Achat\cmd\cmdController@cmdfilter');
    Route::get('allreceptions', 'Achat\cmd\cmdController@allreceptions');
    Route::get('allmodifs', 'Achat\cmd\cmdController@allmodifs');
    Route::post('receptionfilter', 'Achat\cmd\cmdController@receptionfilter');
    Route::post('addbon/{id}', 'Achat\cmd\cmdController@addbon');
    Route::get('getbon/{id}', 'Achat\cmd\cmdController@getbon');
    Route::get('getbonbycmdrecid/{id}', 'Achat\cmd\cmdController@getbonbycmdrecid');
    Route::post('imprimercmdfilter/{id}', 'Achat\cmd\cmdController@imprimercmdfilter');
    Route::get('suprimerBon/{id}', 'Achat\cmd\cmdController@suprimerBon');
    Route::get('bons/{corbeille}', 'Achat\cmd\cmdController@bons');
    Route::post('filterbons/{corbeille}', 'Achat\cmd\cmdController@filterbons');

    Route::post('addRetour/{id}', 'Achat\cmd\cmdController@addRetour');
    Route::get('getRetour/{id}', 'Achat\cmd\cmdController@getRetour');
    Route::get('deleteRetour/{id}', 'Achat\cmd\cmdController@deleteRetour');
    Route::get('getRetours/{corbeille}', 'Achat\cmd\cmdController@getRetours');
    Route::post('filterRetours/{corbeille}', 'Achat\cmd\cmdController@filterRetours'); // rien
     
    
    Route::get('getFoursHasNonFacturedBons', 'Achat\cmd\cmdController@getFoursHasNonFacturedBons');
    Route::post('getFournisseurBons/{id}', 'Achat\cmd\cmdController@getFournisseurBons');
    Route::post('addFouFilter/{id}', 'Achat\cmd\cmdController@addFouFilter');
    Route::post('facturer/{id}', 'Achat\cmd\cmdController@facturer');
    Route::get('getFacture/{id}', 'Achat\cmd\cmdController@getFacture');
    Route::get('factures', 'Achat\cmd\cmdController@factures');
    Route::post('factureFilter/{exist}', 'Achat\cmd\cmdController@factureFilter');
    Route::get('suprimerFacture/{id}', 'Achat\cmd\cmdController@suprimerFacture');
    Route::get('restaurerFacture/{id}', 'Achat\cmd\cmdController@restaurerFacture');
    
    
    

    
    
    
   // Route::get('getform', 'Magasin\article\articlesController@getform');

    
});



Route::group([
    'middleware' => 'jwt.auth', 
    'prefix' => 'magasin/article'
], function ($router) {
    Route::post('articles', 'Magasin\article\articlesController@indexsearch');
    Route::post('getArticleAtelierListSearch', 'Magasin\article\articlesController@getArticleAtelierListSearch');
    
    

    Route::get('getform/{id}', 'Magasin\article\articlesController@getform');
    Route::post('setform', 'Magasin\article\articlesController@setform');
    Route::post('addarticle', 'Magasin\article\articlesController@addarticle');
    Route::post('getarticle/{id}', 'Magasin\article\articlesController@getarticle');
    Route::post('editarticle/{id}', 'Magasin\article\articlesController@editarticle');
    Route::get('getlistesousfamilles/{id}', 'Magasin\article\articlesController@getlistesousfamilles');
   // Route::get('articles/{id}', 'Magasin\article\articlesController@show');
   Route::get('getfamilles', 'Magasin\article\articlesController@getfamilles');
   Route::get('getemplacements', 'Magasin\article\articlesController@getemplacements');
   Route::get('getunites', 'Magasin\article\articlesController@getunites');
   Route::get('getfiles/{id}', 'Magasin\article\articlesController@getfiles');
   Route::post('getarticles/{exist}', 'Magasin\article\articlesController@getarticles');
   Route::post('articlefilter/{exist}', 'Magasin\article\articlesController@articlefilter');
   Route::get('getformfilter/{id}', 'Magasin\article\articlesController@getformfilter');
 
   Route::post('getArticlesPerPage', 'Magasin\article\articlesController@getArticlesPerPage');

   

});

Route::group([
    'prefix' => 'magasin/outils', 
    'middleware' => 'jwt.auth'
], function ($router) {
    Route::post('getOutilTables/{id}', 'Magasin\article\articlesController@getOutilTables');
    Route::post('addOutil', 'Magasin\article\articlesController@addOutil');
    Route::get('addOutil', 'Magasin\article\articlesController@addOutil');
    Route::post('editOutil/{id}', 'Magasin\article\articlesController@editOutil');
    Route::post('getOutils/{corbeille}', 'Magasin\article\articlesController@getOutils');
    Route::post('filterOutils/{corbeille}', 'Magasin\article\articlesController@filterOutils');
    Route::get('deleteOutil/{id}', 'Magasin\article\articlesController@deleteOutil');
    

    Route::post('getUseAndTables/{id}', 'Magasin\article\articlesController@getUseAndTables');
    Route::post('addUse', 'Magasin\article\articlesController@addUse');
    Route::post('editUse/{id}', 'Magasin\article\articlesController@editUse');
    Route::post('getUses/{corbeille}', 'Magasin\article\articlesController@getUses');
    Route::post('filterUses/{corbeille}', 'Magasin\article\articlesController@filterUses');
    Route::get('Cloture/{id}', 'Magasin\article\articlesController@Cloture');
    Route::get('deleteUse/{id}', 'Magasin\article\articlesController@deleteUse');
    
    
});


Route::group([
    'prefix' => 'magasin/inventaire',
    'middleware' => 'jwt.auth' 
], function ($router) {

    Route::get('getArticlesHasSFamID/{id}', 'Magasin\article\articlesController@getArticlesHasSFamID');
    Route::post('getArticlesByArtIDs', 'Magasin\article\articlesController@getArticlesByArtIDs');
    Route::post('inventaire', 'Magasin\article\articlesController@inventaire');
    Route::post('getIntervenantsByInterIDs', 'Magasin\article\articlesController@getIntervenantsByInterIDs');
    Route::get('getInventaire/{id}', 'Magasin\article\articlesController@getInventaire');
    Route::get('getInventaires', 'Magasin\article\articlesController@getInventaires');
    Route::post('filterInventaires', 'Magasin\article\articlesController@filterInventaires');
    Route::get('correction/{id}', 'Magasin\article\articlesController@correction');

});

Route::group([
    'prefix' => 'magasin',
    'middleware' => 'jwt.auth' 
], function ($router) {
    Route::post('bsm/getBsm/{id}', 'Magasin\magasin@getBsm');
    Route::post('bsm/getBsms', 'Magasin\magasin@getBsms');
    Route::post('bsm/acceptBsm/{id}', 'Magasin\magasin@acceptBsm');
    
    Route::post('bso/getBso/{id}', 'Magasin\magasin@getBso');
    Route::post('bso/acceptBso/{id}', 'Magasin\magasin@acceptBso');
    Route::post('bso/getBsos', 'Magasin\magasin@getBsos');

    


    Route::get('use/termineUtilisation/{id}', 'Magasin\magasin@termineUtilisation');
    Route::get('use/terminerTous/{id}', 'Magasin\magasin@terminerTous');
    Route::get('use/getUse/{id}', 'Magasin\magasin@getUse');
    Route::get('use/getUse2/{id}', 'Magasin\magasin@getUse2');
    Route::post('use/addUse', 'Magasin\magasin@addUse');
    Route::post('use/editUse/{id}', 'Magasin\magasin@editUse');
    
    
    Route::post('retour/getRetour/{id}', 'Magasin\magasin@getRetour');
    Route::post('retour/addRetour/{id}', 'Magasin\magasin@addRetour');
    Route::post('retour/getRetours', 'Magasin\magasin@getRetours');
    Route::post('retour/getRetourForAff/{id}', 'Magasin\magasin@getRetourForAff');
    
    

    
});



Route::group([
    'prefix' => 'equipement',
    'middleware' => 'jwt.auth' 
], function ($router) {
    
    Route::get('getNodeDet/{NodeID}', 'Equipement\equipement@getNodeDet');
    Route::post('addEquipement/{PereID}', 'Equipement\equipement@addEquipement');
    Route::get('deleteEquipement/{id}', 'Equipement\equipement@deleteEquipement');
    Route::post('getChilds/{id}', 'Equipement\equipement@getChilds');
    Route::get('getNodeJustChilds/{id}', 'Equipement\equipement@getNodeJustChilds');
    Route::post('getNodeByNodeIDAndByNiveau/{NodeID}', 'Equipement\equipement@getNodeByNodeIDAndByNiveau');
    Route::get('getNode/{EquipementID}', 'Equipement\equipement@getNode');
    Route::get('getRacine/{niveau}', 'Equipement\equipement@getRacine');
    Route::post('getNodeRacine/{NodeID}', 'Equipement\equipement@getNodeRacine');
    Route::get('getNiveauMax', 'Equipement\equipement@getNiveauMax');
    Route::get('getNiveauDetByEquipementID/{id}', 'Equipement\equipement@getNiveauDetByEquipementID');
    

    Route::get('getAnomalie/{id}', 'Equipement\equipement@getAnomalie');
    Route::post('addAnomalie/{id}', 'Equipement\equipement@addAnomalie');
    Route::get('getListeAnomalies', 'Equipement\equipement@getListeAnomalies');
    Route::post('filterAnomalie', 'Equipement\equipement@filterAnomalie');
    Route::post('FusionnerAnomalie', 'Equipement\equipement@FusionnerAnomalie');
    Route::post('anomalieSearch', 'Equipement\equipement@anomalieSearch');
    Route::get('getAnomaliesByEquipementID/{id}', 'Equipement\equipement@getAnomaliesByEquipementID');
    
    Route::get('getTache/{id}', 'Equipement\equipement@getTache');
    Route::post('addTache/{id}', 'Equipement\equipement@addTache');
    //Route::get('getListeAnomalies', 'Equipement\equipement@getListeAnomalies');
    //Route::post('filterAnomalie', 'Equipement\equipement@filterAnomalie');
    //Route::post('FusionnerAnomalie', 'Equipement\equipement@FusionnerAnomalie');
    //Route::post('anomalieSearch', 'Equipement\equipement@anomalieSearch');
    Route::get('getTachesByEquipementID/{id}', 'Equipement\equipement@getTachesByEquipementID');

    
    

    Route::post('getSemblables/{quoi}', 'Equipement\equipement@getSemblables');



    Route::post('datetimetest', 'Equipement\equipement@datetimetest');
    Route::get('affectDateTime/{id}', 'Equipement\equipement@affectDateTime');
    
    Route::get('getEquipementDeuxTest/{id}', 'Equipement\equipement@getEquipementDeuxTest');
    Route::get('getAllDetEquipement/{id}', 'Equipement\equipement@getAllDetEquipement');
    Route::post('editEquipement/{id}', 'Equipement\equipement@editEquipement');

    Route::get('getArticlesMagasinAtelierByEquipementID/{id}', 'Equipement\equipement@getArticlesMagasinAtelierByEquipementID');
    Route::post('AddOrEditEquipementArticle/{id}', 'Equipement\equipement@AddOrEditEquipementArticle');
    
    
    
});

Route::group([
    'prefix' => 'correctif',
    'middleware' => 'jwt.auth' 
], function ($router) {
    Route::get('di/getDi/{id}', 'Correctif\correctif@getDi');//-------
    Route::get('di/getDiForAffichage/{id}', 'Correctif\correctif@getDiForAffichage');//-------
    
    Route::post('di/addDi', 'Correctif\correctif@addDi');
    Route::get('di/deleteDi/{id}', 'Correctif\correctif@deleteDi');
    
    Route::post('di/editDi/{id}', 'Correctif\correctif@editDi');
    Route::post('di/getDi/{id}', 'Correctif\correctif@getDi');//--------
    Route::post('di/getDis', 'Correctif\correctif@getDis');//--------
    Route::post('di/planifierDi/{id}', 'Correctif\correctif@planifierDi');//--------
    Route::get('di/deplanifierDi/{id}', 'Correctif\correctif@deplanifierDi');//--------
    
    Route::get('di/getPlan/{id}', 'Correctif\correctif@getPlan');//--------
    Route::get('di/getPlans', 'Correctif\correctif@getPlans');//--------
    Route::post('di/getPlansForList', 'Correctif\correctif@getPlansForList');//--------
    
    Route::get('di/executerPlan/{id}', 'Correctif\correctif@executerPlan');//--------
    Route::post('di/getEventsPlans', 'Correctif\correctif@getEventsPlans');//--------
    

    Route::get('plan/systemePlanAutoToOt/{id}', 'Correctif\correctif@systemePlanAutoToOt');
    Route::get('plan/syestemePlans', 'Correctif\correctif@syestemePlans');
    Route::get('plan/deletePlan/{id}', 'Correctif\correctif@deletePlan');
    

    Route::post('ot/addOt/{isSystem}', 'Correctif\correctif@addOt');
    Route::post('ot/editOt/{id}', 'Correctif\correctif@editOt');
    Route::get('ot/getOt/{id}', 'Correctif\correctif@getOt');
    Route::get('ot/deleteOt/{id}', 'Correctif\correctif@deleteOt');
    Route::post('ot/getOts', 'Correctif\correctif@getOts');
    

    Route::post('bsm/getBsmByID/{id}', 'Correctif\correctif@getBsmByID');
    Route::post('bsm/addBsm/{id}', 'Correctif\correctif@addBsm');
    Route::post('bsm/editBsm/{id}', 'Correctif\correctif@editBsm');
    Route::get('bsm/getBsms/{id}', 'Correctif\correctif@getBsms');
    Route::post('bsm/deleteBsm/{id}', 'Correctif\correctif@deleteBsm');

    
    
    Route::post('bsm/getBsmWithResponse/{id}', 'Correctif\correctif@getBsmWithResponse');

    Route::get('bso/getOutilListSearch', 'Correctif\correctif@getOutilListSearch');
    Route::post('bso/getBsoByID/{id}', 'Correctif\correctif@getBsoByID');
    // Route::get('bso/getBsos/{id}', 'Correctif\correctif@getBsos'); // mabda2iyan zayda just sta3maltha f test 
    Route::post('bso/addBso/{id}', 'Correctif\correctif@addBso');
    Route::post('bso/editBso/{id}', 'Correctif\correctif@editBso');
    Route::post('bso/deleteBso/{id}', 'Correctif\correctif@deleteBso');

    
     
    Route::post('bso/getBsoWithResponse/{id}', 'Correctif\correctif@getBsoWithResponse');
    
    Route::post('bon/getBon', 'Correctif\correctif@getBon');
    Route::get('bon/getBonForAff/{id}', 'Correctif\correctif@getBonForAff');
    Route::get('bon/getBonForAff2/{id}', 'Correctif\correctif@getBonForAff2');
    Route::post('bon/addBon/{OtID}', 'Correctif\correctif@addBon');
    Route::post('bon/getBons', 'Correctif\correctif@getBons');
    Route::post('bon/editBon/{id}', 'Correctif\correctif@editBon');
    
    
    
    
    
    
    

    


    


});



Route::group([
    'prefix' => 'preventif',
    'middleware' => 'jwt.auth' 
], function ($router) {
   
    // Route::get('di/deplanifierDi/{id}', 'Preventif\preventif@deplanifierDi');
     Route::post('intervention/addIntervention', 'Preventif\preventif@addIntervention');
     Route::post('intervention/editIntervention/{id}', 'Preventif\preventif@editIntervention');
     Route::post('intervention/decalageIntervention/{id}', 'Preventif\preventif@decalageIntervention');
     Route::get('intervention/getIntervention/{id}', 'Preventif\preventif@getIntervention');
     Route::post('intervention/getInterventions', 'Preventif\preventif@getInterventions');
     Route::get('intervention/deleteIntervention/{id}', 'Preventif\preventif@deleteIntervention');

     
    
     

     Route::post('plan/getPlans', 'Preventif\preventif@getPlans');
     Route::post('plan/reporterPlan/{id}', 'Preventif\preventif@reporterPlan');

     Route::post('plan/getEventsIPs', 'Preventif\preventif@getEventsIPs');

     
     


     Route::get('otp/deleteOtp/{id}', 'Preventif\preventif@deleteOtp');
     Route::post('otp/getOtp', 'Preventif\preventif@getOtp');
     Route::post('otp/addOtp', 'Preventif\preventif@addOtp');
     Route::post('otp/getOtps', 'Preventif\preventif@getOtps');
     Route::post('otp/editOtp/{id}', 'Preventif\preventif@editOtp');
     //Route::post('otp/getOtp/{id}', 'Preventif\preventif@getOtp');
     Route::post('otp/addReservationBsm/{id}', 'Preventif\preventif@addReservationBsm');
     Route::post('otp/getReservationBsmByID/{id}', 'Preventif\preventif@getReservationBsmByID');
     Route::post('otp/editReservationBsm/{id}', 'Preventif\preventif@editReservationBsm');

     Route::post('otp/addIntReservation/{id}', 'Preventif\preventif@addIntReservation');
     Route::post('otp/editIntReservation/{id}', 'Preventif\preventif@editIntReservation');
     Route::get('otp/getIntReservation/{id}', 'Preventif\preventif@getIntReservation');
     Route::get('otp/ViderArticlesReserved/{id}', 'Preventif\preventif@ViderArticlesReserved');
     Route::get('otp/viderIntervenantsReserved/{id}', 'Preventif\preventif@viderIntervenantsReserved');
     
     
     Route::get('TestGetAncienDDR/{id}', 'Preventif\preventif@TestGetAncienDDR');

     Route::post('otp/IsIntReserved', 'Preventif\preventif@IsIntReserved');

     

          
     


     Route::post('bonp/getBonp', 'Preventif\preventif@getBonp');
     Route::post('bonp/addBonp/{OtpID}', 'Preventif\preventif@addBonp');
     Route::post('bonp/editBonp/{BonpID}', 'Preventif\preventif@editBonp');
     Route::post('bonp/getBonps', 'Preventif\preventif@getBonps');
     Route::get('bonp/getBonpForAff/{id}', 'Preventif\preventif@getBonpForAff');
     //Route::get('bonp/getUnionBsoOtp/{id}', 'Preventif\preventif@getUnionBsoOtp');
     
     


     // just for test
     Route::get('otp/getBonJustTest', 'Preventif\preventif@getBonJustTest');

     
     

     
     
     
     
     

     
});



Route::group([
    'middleware' => 'jwt.auth', 
    'prefix' => 'mypages'
], function ($router) {

    Route::get('countDi', 'Mypages\mypages@countDi');
  
});


Route::group([
    'middleware' => 'jwt.auth', 
    'prefix' => 'notification'
], function ($router) {
    Route::get('mynotifications', 'Mypages\mypages@mynotifications');
    Route::get('markAsRead/{id}', 'Mypages\mypages@markAsRead');
    Route::get('markAsView/{type}', 'Mypages\mypages@markAsView');
});






















