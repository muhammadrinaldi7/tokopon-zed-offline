<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures only admin/superadmin/cs roles can access admin routes.
 * Customers will be redirected to the homepage.
 */
class EnsureIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Memeriksa apakah user memiliki setidaknya 1 permission apa saja, atau merupakan superadmin
        if ($user->getAllPermissions()->isEmpty() && !$user->hasRole('superadmin')) {
            return redirect('/')->with('error', 'Anda tidak memiliki akses ke halaman admin.');
        }

        return $next($request);
    }
}
