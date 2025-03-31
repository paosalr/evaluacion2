<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CorsMiddleware
{
    public function handle(Request $request, Closure $next)
    {

        $allowedOrigin = 'http://localhost:5173';

        $headers = [
            'Access-Control-Allow-Origin' => $allowedOrigin,
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, Accept, X-Requested-With',
        ];

        \Log::debug('Cors middleware 1',[
            'request'=>$request->all()
        ]);

        $response = $next($request);

        foreach ($headers as $key => $value) {
            $response->header($key, $value);
        }

        \Log::debug('Cors middleware 2',[
            'request'=>$request->all()
        ]);

        return $response;
    }
}
