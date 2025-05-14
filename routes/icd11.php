<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Icd11Controller;

/*
|--------------------------------------------------------------------------
| Rutas de ICD-11
|--------------------------------------------------------------------------
|
| Aquí se registran todas las rutas relacionadas con ICD-11 de la OMS.
|
*/

// Determinar si estas rutas se están cargando desde api.php o web.php
if (request()->is('api*')) {
    // Rutas para la API (cuando se cargan desde api.php)
    Route::prefix('icd11')->group(function () {
        Route::get('/search', [Icd11Controller::class, 'search']);
        Route::get('/entity/{entityId}', [Icd11Controller::class, 'getEntity']);
        Route::get('/entity/{entityId}/children', [Icd11Controller::class, 'getChildren']);
        Route::get('/get-token', [Icd11Controller::class, 'getApiToken']);
        Route::get('/code/{code}', [Icd11Controller::class, 'getByCode']);
        Route::get('/disease/{code}', [Icd11Controller::class, 'getDetailedDiseaseByCode']);
        Route::post('/entity-by-uri', [Icd11Controller::class, 'getEntityByUri']);
    });
} else {
    // Rutas para la interfaz web (cuando se cargan desde web.php)
    Route::prefix('icd11')->group(function () {
        Route::get('/', [Icd11Controller::class, 'index'])->name('icd11.index');
        Route::get('/search', [Icd11Controller::class, 'search'])->name('icd11.search');
        Route::get('/entity/{entityId}', [Icd11Controller::class, 'getEntity'])->name('icd11.entity');
        Route::get('/entity/{entityId}/children', [Icd11Controller::class, 'getChildren'])->name('icd11.children');
        Route::get('/embedded-tool', [Icd11Controller::class, 'embeddedTool'])->name('icd11.embedded-tool');
        Route::get('/coding-tool', [Icd11Controller::class, 'codingTool'])->name('icd11.coding-tool');
        Route::get('/disease/{code}', [Icd11Controller::class, 'getDetailedDiseaseByCode'])->name('icd11.disease');
        Route::get('/disease-tester', [Icd11Controller::class, 'diseaseTester'])->name('icd11.disease-tester');
        Route::get('/api-docs', [Icd11Controller::class, 'apiDocs'])->name('icd11.api-docs');
    });
}
