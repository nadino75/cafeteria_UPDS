<?php

namespace App\Providers;

use App\Models\GastoOperativo;
use App\Services\FifoService;
use App\Services\TurnoService;
use App\Services\VentaService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FifoService::class);
        $this->app->singleton(TurnoService::class);
        $this->app->singleton(VentaService::class, fn ($app) => new VentaService(
            $app->make(FifoService::class)
        ));
    }

    public function boot(): void
    {
        // El parámetro {gasto} del resource route apunta a GastoOperativo
        Route::model('gasto', GastoOperativo::class);
    }
}
