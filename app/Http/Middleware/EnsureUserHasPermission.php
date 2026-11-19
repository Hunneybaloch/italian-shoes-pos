<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active || ! $user->hasPermission($permission)) {
            abort(403, 'You are not authorized to perform this action.');
        }

        if ($user->branch_id && (! $user->branch || ! $user->branch->is_active)) {
            abort(403, 'Your assigned branch is inactive.');
        }

        return $next($request);
    }
}
