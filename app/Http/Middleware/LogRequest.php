<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogRequest
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        Log::channel('api_activity')->info("Request: {$request->method()} {$request->path()} by User: {$request->user()->id}");
        return $response;
    }
}