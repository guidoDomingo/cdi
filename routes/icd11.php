<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Icd11Controller;

/*
|--------------------------------------------------------------------------
| Rutas de la API ICD-11
|--------------------------------------------------------------------------
|
| Aquí se registran todas las rutas relacionadas con la API ICD-11 de la OMS.
| Estas rutas pueden ser incluidas en web.php o api.php según sea necesario.
|
*/

// Rutas para la interfaz web
Route::prefix('icd11')->group(function () {
    Route::get('/', [Icd11Controller::class, 'index'])->name('icd11.index');
    Route::get('/search', [Icd11Controller::class, 'search'])->name('icd11.search');
    Route::get('/entity/{entityId}', [Icd11Controller::class, 'getEntity'])->name('icd11.entity');
    Route::get('/entity/{entityId}/children', [Icd11Controller::class, 'getChildren'])->name('icd11.children');
    Route::get('/embedded-tool', [Icd11Controller::class, 'embeddedTool'])->name('icd11.embedded-tool');
    Route::get('/coding-tool', [Icd11Controller::class, 'codingTool'])->name('icd11.coding-tool');
});

// Rutas para la API
Route::prefix('api/icd11')->group(function () {
    Route::get('/search', [Icd11Controller::class, 'search']);
    Route::get('/entity/{entityId}', [Icd11Controller::class, 'getEntity']);
    Route::get('/entity/{entityId}/children', [Icd11Controller::class, 'getChildren']);
    Route::get('/get-token', [Icd11Controller::class, 'getApiToken']);
});
