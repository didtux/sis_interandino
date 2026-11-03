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
        // gestion_actual() y demás funciones globales (ver app/helpers.php).
        require_once app_path('helpers.php');
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

        try {
            $sistemaConfig = \App\Models\SistemaConfiguracion::actual();
        } catch (\Throwable $e) {
            $sistemaConfig = null;
        }
        \Illuminate\Support\Facades\View::share('sistemaConfig', $sistemaConfig);

        // Directiva @puede('modulo_slug', 'crear') ... @endpuede
        \Illuminate\Support\Facades\Blade::if('puede', function ($modSlug, $accion = 'ver') {
            $user = auth()->user();
            return $user && \App\Support\Permisos::puede((int) $user->rol_id, $modSlug, $accion);
        });

        // Capacidades especiales como Gate: @can('notas.aprobar'), $user->can(...),
        // $this->authorize(...). El administrador las tiene todas.
        \Illuminate\Support\Facades\Gate::before(function ($user) {
            return (int) $user->rol_id === 1 ? true : null;
        });
        foreach (array_keys(config('permisos.capacidades', [])) as $cap) {
            \Illuminate\Support\Facades\Gate::define($cap, fn($user) => $user->tieneCapacidad($cap));
        }
    }

}
