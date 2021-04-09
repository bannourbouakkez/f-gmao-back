<?php

namespace App\Http\Controllers\Mypages;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;



use App\User;
use Carbon\Carbon;
use JWTFactory;
use JWTAuth;
use phpDocumentor\Reflection\Types\Boolean;

use App\Model\Magasin\article;

class mypages extends Controller
{
    
  

  protected $type_alerts=["App\Notifications\Alerte","App\Notifications\Alerte2"];

  public function countDi(){
    $countdi=article::where('exist','=',1)->count();
    return response()->json(['success'=>true,'countDi'=>$countdi]);
   }


   public function mynotifications(){ 
     //$ani->unreadNotification->isEmpty(); //foreach( $ani->unreadNotification as $notification){}
     //$notifications = $ani->notifications->whereNotIn('type',$this->type_alerts)->all();
     
    $ani=User::find(JWTAuth::user()->id);
    $notifications_i = $ani->notifications;
    $notifications=Array(); $alerts=Array();
    foreach( $notifications_i as $notification){
     if(!in_array($notification->type,$this->type_alerts)){array_push($notifications,$notification);}
     else{array_push($alerts,$notification);}
    }

    //$notifications = $ani->notifications->whereNotIn('type',$this->type_alerts)->all();
    //$notifications=DB::table('notifications')->where('notifiable_id',JWTAuth::user()->id)
    //                  ->whereNotIn('type',$this->type_alerts)->get();

    $unread_count=$ani->unreadNotifications->whereNotIn('type',$this->type_alerts)->count();
    $unview_count=$ani->notifications->whereNotIn('type',$this->type_alerts)
                                     ->where('view_at','=',NULL)->count();

    $unread_alerts_count=$ani->unreadNotifications->whereIn('type',$this->type_alerts)->count();
    $unview_alerts_count=$ani->notifications->whereIn('type',$this->type_alerts)
                                           ->where('view_at','=',NULL)->count();

    return response()->json(['notification'=>$notifications,'unread_count'=>$unread_count,'unview_count'=>$unview_count,
                             'alerts'=>$alerts,'unread_alerts_count'=>$unread_alerts_count,'unview_alerts_count'=>$unview_alerts_count]);
   }

    public function markAsRead($id){ 
     $ani=User::find(JWTAuth::user()->id);
     $ani->unreadNotifications->where('id', $id)->markAsRead();
     return response()->json($id);
    }


    public function markAsView($type){ 
      // $ani=User::find(JWTAuth::user()->id);
      //$ani->unreadNotifications->markAsView(); //->markAsRead();
      //$ani->notifications->where('view_at','=',NULL)->update(['view_at'=>Carbon::now()]);
      //notification::where('notifiable_id',JWTAuth::user()->id)->where('view_at','=',NULL)->update(['read_at'=>Carbon::now()])
      //return response()->json($id);

      if($type=='notifications'){
        DB::table('notifications')->where('notifiable_id',JWTAuth::user()->id)->whereNotIn('type',$this->type_alerts)
                                  ->update(['view_at'=>Carbon::now()]);
      }
      if($type=='alerts'){
        DB::table('notifications')->where('notifiable_id',JWTAuth::user()->id)->whereIn('type',$this->type_alerts)
                                  ->update(['view_at'=>Carbon::now()]);
      }

     }

    

   


}
