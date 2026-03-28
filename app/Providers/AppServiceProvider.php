<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
  public function boot()
    {
        Schema::defaultStringLength(191);
        Paginator::useBootstrap();

        // Directiva @puede('modulo_slug', 'crear') ... @nopuede
        \Illuminate\Support\Facades\Blade::if('puede', function ($modSlug, $accion = 'ver') {
            $user = auth()->user();
            if (!$user) return false;
            if ($user->rol_id == 1) return true;
            $campo = 'perm_' . $accion;
            return \App\Models\RolPermiso::where('rol_id', $user->rol_id)
                ->whereHas('modulo', fn($q) => $q->where('mod_slug', $modSlug))
                ->where($campo, 1)
                ->exists();
        });
    }

}
