<?php

namespace App\Http\Middleware;

use Closure;

class Cors
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
       // return $next($request)->header("Access-Control-Allow-Origin:*");
       /*
       header("Access-Control-Allow-Origin: *");
       $headers = [
           'Access-Control-Allow-Origin-Methods' => 'POST,GET,OPTIONS,PUT,DELETE',
           'Access-Control-Allow-Origin-Methods' => 'Content-Type,X-Auth-Token,Origin,Authorization'
       ];

       if ($request->getMethod() == "OPTIONS") {
           return response()->json('OK', 200, $headers);
       }
       $response = $next($request);
       foreach ($headers as $key => $value) {
           $response->header($key, $value);
       }
       return $response;
       */

      return $next($request)
      ->header('Access-Control-Allow-Origin', '*')
      ->header('Access-Control-Allow-Methods', 'GET, POST, PATCH, PUT, DELETE, OPTIONS')
      ->header('Access-Control-Allow-Headers', 'Origin, Content-Type, X-Auth-Token, Authorization');   
      
      
    }
}
