<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Contoh pemakaian di routes: ->middleware('role:1,3,4')
     */
    public function handle(Request $request, Closure $next, ...$roleIds): Response
    {
        $user = $request->user();

        if (!$user || !in_array((string) $user->role_id, $roleIds)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
