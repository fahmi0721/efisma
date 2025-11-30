<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next,$permissions): Response
    {
        $user = Auth::user();
        if (! $user) {
            abort(403, 'Unauthorized.');
        }

        // split multiple permission slug
        $need = explode('|', $permissions);

        // cache permission user selama 60 detik (optional)
        $cacheKey = "user_permissions_{$user->id}";
        $userPermissions = Cache::remember($cacheKey, 60, function () use ($user) {
            return $user->getAllPermissionSlugs(); // method di User model (lihat bawah)
        });

        // cek minimal 1 matched permission
        foreach ($need as $perm) {
            if (in_array(trim($perm), $userPermissions)) {
                return $next($request);
            }
        }

        abort(403, 'Access denied.');
    }
}
