<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::if('canAccess', function ($permissionSlug) {
            $user = auth()->user();
            if (! $user) return false;
            // dukung multiple permission dipisah |
            $need = explode('|', $permissionSlug);
            foreach ($need as $p) {
                if ($user->hasPermission(trim($p))) return true;
            }
            return false;
        });
    }
}
