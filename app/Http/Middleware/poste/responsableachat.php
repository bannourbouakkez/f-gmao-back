<?php

namespace App\Http\Middleware\poste;
use Illuminate\Support\Facades\Auth;
use Closure;
use JWTFactory;
//use JWTAuth;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\User;

class responsableachat
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        
        if( ( JWTAuth::user()->poste ) =="ResponsableAchat"){return $next($request);}
        else{return response()->json(['error' => 'Unauthorized'], 401);}
        
        //return response()->json(JWTAuth::user());
    }
}
