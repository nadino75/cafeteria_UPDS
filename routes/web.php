<?php

use Illuminate\Support\Facades\Route;

// Todas las rutas web sirven el SPA de Vue
// Vue Router maneja la navegación del lado del cliente
Route::get('/{any?}', function () {
    return view('spa');
})->where('any', '.*');
