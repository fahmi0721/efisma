<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetEntitasScope
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user && $user->level == 'entitas') {
            // Simpan entitas_id ke request global
            $request->merge([
                'entitas_scope' => $user->entitas_id
            ]);

            // Paksa semua input entitas_id = user->entitas_id
            $request->request->set('entitas_id', $user->entitas_id);
        }
        return $next($request);
    }
}
