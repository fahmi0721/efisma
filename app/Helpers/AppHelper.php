<?php

use Illuminate\Support\Facades\DB;

if (!function_exists('getNamaAplikasi')) {
    function getNamaAplikasi()
    {
        return DB::table('base_sistem')->value('nama_aplikasi') ?? 'Aplikasi';
    }
}

if (!function_exists('getLogoAplikasi')) {
    function getLogoAplikasi()
    {
        $path = config('custom.upload_images');
        $logo = DB::table('base_sistem')->value('logo');

        if ($logo) {
            return $path . $logo;
        }

        return $path."avatar.png"; // fallback default
    }
}

if (!function_exists('getFaviconAplikasi')) {
    function getFaviconAplikasi()
    {
        $path = config('custom.upload_images');
        $favicon = DB::table('base_sistem')->value('favicon');

        if ($favicon) {
            return $path . $favicon;
        }

        return $path . "avatar.png"; // fallback default
    }
}


if (!function_exists('setActive')) {
    /**
     * Cek menu aktif berdasarkan route name
     *
     * @param array|string $routeNames
     * @param string $class
     * @return string
     */
    function setActive($routeNames, $class = 'active') {
        if (is_array($routeNames)) {
            foreach ($routeNames as $route) {
                if (\Route::currentRouteName() == $route) {
                    return $class;
                }
            }
        } else {
            if (\Route::currentRouteName() == $routeNames) {
                return $class;
            }
        }
        return '';
    }
}

?>