<?php

namespace App\Http\Middleware;

use Closure;

class VerifyAppTokenMiddleware
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
        if ($request->get('token') == env('SLACK_APP_TOKEN')) {
            return $next($request);
        }
    }
}
