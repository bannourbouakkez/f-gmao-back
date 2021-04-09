<?php

namespace App\Http\Middleware\poste;
use Closure;
use JWTFactory;
use JWTAuth;
use App\User;

class admin
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
        if(JWTAuth::user()->poste=="admin"){return $next($request);}
        else{return response()->json(['error' => 'Unauthorized'], 401);}
        
    }
}
