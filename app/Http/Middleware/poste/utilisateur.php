<?php

namespace App\Http\Middleware\poste;
use Closure;
use JWTFactory;
use JWTAuth;
use App\User;

class utilisateur
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
        if(JWTAuth::user()->poste=="utilisateur"){return $next($request);}
        else{return response()->json(['error' => 'Unauthorized'], 401);}
    }
}
