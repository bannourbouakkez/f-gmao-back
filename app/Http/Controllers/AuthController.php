<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use JWTFactory;
use JWTAuth;
use App\User;
use App\Model\Auth\post;
use App\Model\Auth\user_post;

class AuthController extends Controller
{
    public function __construct()
    { 
      $this->middleware('jwt.auth', ['except' => ['login','refresh','register','registerTemporelle']]);
      $this->middleware('jwt.refresh')->only('refresh');   
    }

    public function login()
    {
        $credentials = request(['email', 'password']);
        //if (! $token = JWTAuth::claims(['who' => 'admin'])->attempt($credentials)) {
          
          if (!JWTAuth::attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
          }
        
        
        $posts_arr=user_post::select('post_id')->where('user_id','=',JWTAuth::user()->id)->get();
        $posts="";
        foreach($posts_arr as $poste){
           $PostID=$poste['post_id'];
           $postName=post::select('post')->where('PostID','=',$PostID)->first();
           $postName=$postName->post;
           $posts.=$postName.',';

        }
        $token=JWTAuth::claims(['id'=>JWTAuth::user()->id,'name'=>'','Posts' =>$posts])->attempt($credentials);
        return $this->respondWithToken($token);
    }

    public function register(){}


    public function registerTemporelle(Request $request){
        //id	name	prename	age	email	email_verified_at	password	poste
        return User::create([
            'name'=>'Methode',
            'prename'=>'Methode',
            'age'=>33,
            'email'=>'Methode@Methode.com',
            'password'=>bcrypt('Methode'),
            'poste'=>'user'
            ]);
    }


    public function logout()
    {
        JWTAuth::parseToken()->invalidate();
        return response()->json(['message' => 'Successfully logged out']);
    }

    public function refresh()
    {
       $token = JWTAuth::refresh();
       return $this->respondWithToken($token);
    }

    protected function respondWithToken($token)
    {
     return response()->json([ 'jwt'=>$token,'refreshToken'=>'']);
    }

    public function me(Request $request)
    {
        return response()->json(JWTAuth::user());
    }








//====================================================================> Bruillant 


/*
    public function metest()
    {
        
        $token = JWTAuth::getToken(); 
        $payload = JWTAuth::decode($token);
        //$validate = $payload->get('who');
        return response()->json([JWTAuth::user(),$payload]);
        

        return response()->json(['error' => 'ti error w barra'],401);
    }

    public function verifyloggingurl(){
        return JWTAuth::parseToken()->authenticate();
    }

    //$refreshtoken=JWTAuth::setToken($token)->refresh()->setTTL(2);
        //$refreshtoken=JWTAuth::setToken($token)->refresh();
        return response()->json([
            'jwt'=>$token,
            'refreshToken'=>''
            //'refreshToken'=>$refreshtoken
             //'refreshToken'=>JWTAuth::refresh(JWTAuth::getToken())
            
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60
    // refresh() 
     //$refreshtoken = request(['refreshToken']);
        //return $this->respondWithToken($refreshtoken);
        //return ; // $this->respondWithToken(JWTAuth::refresh());
        // $refreshtoken=JWTAuth::setToken($token)->refresh();
        //JWTAuth::refresh($token);
         //$token = JWTAuth::getToken();
        // $token="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC8xMjcuMC4wLjE6ODAwMFwvYXBpXC9hdXRoXC9sb2dpbiIsImlhdCI6MTU3OTAyMzY5NSwiZXhwIjoxNTc5MDIzNzU1LCJuYmYiOjE1NzkwMjM2OTUsImp0aSI6IlNxN2IwcHdrNlQyeW5jQXkiLCJzdWIiOjEsInBydiI6Ijg3ZTBhZjFlZjlmZDE1ODEyZmRlYzk3MTUzYTE0ZTBiMDQ3NTQ2YWEiLCJ3aG8iOiJhZG1pbiJ9.b7L98bi_H7WvM0DmXgsJJjkFGzGBzpT7uMM8dzb6hoQa";  
        //$token=$token;
        //return $this->respondWithToken(JWTAuth::refresh($token));
       
       // JWTAuth::parseToken();
       // $token = JWTAuth::getToken();
       //if(!$token){return response()->json(['error' => 'EXPIRED AND NOT REFRESHED'],401); }
*/    
       





}

