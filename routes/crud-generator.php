<?php

use Illuminate\Support\Facades\Route;

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
    ->group(function () {
        // Wizard index page
        Route::get('/', function () {
            // This will be handled by the CrudGeneratorController
            // which needs to be moved to the package or registered via the app
            return \Inertia\Inertia::render('CrudGenerator/Index', [
                'models' => app(\Rsk\CrudGenerator\Services\ModelIntrospector::class)->getAvailableModels(),
            ]);
        })->name('index');

        // Get model configuration (fields, relationships)
        Route::get('/model/{model}', function (string $model) {
            return app(\Rsk\CrudGenerator\Services\ModelIntrospector::class)->getModelConfig($model);
        })->name('model.config');

        // Generate CRUD
        Route::post('/generate', function (\Illuminate\Http\Request $request) {
            return app(\Rsk\CrudGenerator\Services\CrudGeneratorService::class)->generate($request);
        })->name('generate');
    });
