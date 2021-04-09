<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\User;
use JWTFactory;
use JWTAuth;
use Validator;


class LoginController extends Controller
{
    //
    public function login(Request $request){
        $validator=Validator::make($request->all(),[
            'email'=>'required|string|email|max:255',
            'password'=>'required'
        ]);
        if($validator->fails()){return response()->json($validator->errors()); }
  
      $credentials = $request->only('email','password');
      try{
            if(! $token = JWTAuth::attempt($credentials) ){
           // if(! $token = auth()->attempt($credentials) ){ t5alli token = true ama ma tbadlouch ma3neha token le9dim yab9a sale7 ! 

              return response()->json(['error'=>'invalid user or password'],[401]);
          }
      }catch(JWTException $e){
         return response()->json(['error'=>'could nor create token'],[500]);
      }

      return response()->json(compact('token'));
  


}
}

