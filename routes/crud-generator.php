<?php

use Illuminate\Support\Facades\Route;
use Rsk\CrudGenerator\Http\Controllers\CrudGeneratorController;

/*
|--------------------------------------------------------------------------
| CRUD Generator Routes
|--------------------------------------------------------------------------
|
| These routes provide the UI wizard for the CRUD Generator package.
| They are only loaded in local/development environments.
|
*/

Route::middleware(config('crud-generator.middleware', ['web', 'auth']))
    ->prefix(config('crud-generator.route_prefix', 'admin/crud-generator'))
    ->name('crud-generator.')
    ->controller(CrudGeneratorController::class)
    ->group(function () {
        // Wizard index page
        Route::get('/', 'index')->name('index');

        // Get model configuration (fields, relationships)
        Route::get('/model/{model}', 'getModelConfig')->name('model.config');

        // Generate CRUD
        Route::post('/generate', 'generate')->name('generate');
    });
