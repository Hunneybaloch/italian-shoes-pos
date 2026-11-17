<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->is_active || !$request->user()->hasRole('owner')) {
            abort(403, 'You are not authorized to access this section.');
        }

        return $next($request);
    }
}
