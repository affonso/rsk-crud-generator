<?php

declare(strict_types=1);

namespace Rsk\CrudGenerator\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Rsk\CrudGenerator\Contracts\ConfigurationManagerInterface;

/**
 * Manages CRUD configuration files (routes and navigation).
 *
 * Handles insertion of route declarations and navigation items
 * into their respective configuration files.
 */
class ConfigurationManager implements ConfigurationManagerInterface
{
    /**
     * Add resource routes to the routes file.
     *
     * Inserts the necessary route declarations for the CRUD resource
     * into the designated routes file at the appropriate marker location.
     *
     * @param  string  $model  The model name (e.g., 'User', 'Post')
     * @return array{added: bool, message: string} Result with success status and message
     */
    public function addCrudRoutes(string $model): array
    {
        $routesFile = base_path(config('crud-generator.routes_file'));

        if (! File::exists($routesFile)) {
            return [
                'added' => false,
                'message' => '⚠ Arquivo '.config('crud-generator.routes_file').' não encontrado',
            ];
        }

        $modelStudly = Str::studly($model);
        $routeName = Str::plural(Str::kebab($model));
        $controllerClass = "\\App\\Http\\Controllers\\{$modelStudly}Controller::class";

        // Read current content
        $content = File::get($routesFile);

        // Check if route already exists
        if (str_contains($content, "Route::resource('{$routeName}'")) {
            return [
                'added' => false,
                'message' => "⚠ Rotas para '{$routeName}' já existem",
            ];
        }

        // Generate route line
        $routeLine = "    Route::resource('{$routeName}', {$controllerClass})->names('{$routeName}');";

        // Find the marker and insert before it
        $marker = config('crud-generator.routes_marker');
        if (! str_contains($content, $marker)) {
            return [
                'added' => false,
                'message' => "⚠ Marcador {$marker} não encontrado",
            ];
        }

        // Insert route before marker
        $newContent = str_replace(
            $marker,
            $routeLine."\n    ".$marker,
            $content
        );

        File::put($routesFile, $newContent);

        return [
            'added' => true,
            'message' => '✓ Rotas adicionadas em '.config('crud-generator.routes_file'),
        ];
    }

    /**
     * Add navigation item to the navigation config.
     *
     * Inserts a new navigation entry for the CRUD resource
     * into the navigation configuration file.
     *
     * @param  string  $model  The model name (e.g., 'User', 'Post')
     * @param  string  $icon  The icon identifier for the navigation item (e.g., 'users', 'file-text')
     * @return array{added: bool, message: string} Result with success status and message
     */
    public function addNavigationItem(string $model, string $icon): array
    {
        $navigationFile = config('crud-generator.navigation_file');

        // Remove 'config/' prefix if present (for backward compatibility)
        $navigationFile = preg_replace('#^config[/\\\\]#', '', $navigationFile);

        $configFile = config_path($navigationFile);

        if (! File::exists($configFile)) {
            return [
                'added' => false,
                'message' => '⚠ Arquivo '.str_replace(base_path().'/', '', $configFile).' não encontrado',
            ];
        }

        $title = Str::plural(Str::headline($model));
        $routeName = Str::plural(Str::kebab($model));
        $route = "{$routeName}.index";

        // Read current content
        $content = File::get($configFile);

        // Check if nav item already exists
        if (str_contains($content, "'{$route}'")) {
            return [
                'added' => false,
                'message' => "⚠ Link '{$title}' já existe na sidebar",
            ];
        }

        // Generate nav item line
        $navLine = "        ['title' => '{$title}', 'route' => '{$route}', 'icon' => '{$icon}', 'enabled' => true],";

        // Find the marker and insert before it
        $marker = config('crud-generator.navigation_marker');
        if (! str_contains($content, $marker)) {
            return [
                'added' => false,
                'message' => "⚠ Marcador {$marker} não encontrado",
            ];
        }

        // Insert nav item before marker
        $newContent = str_replace(
            $marker,
            $navLine."\n        ".$marker,
            $content
        );

        File::put($configFile, $newContent);

        return [
            'added' => true,
            'message' => '✓ Link adicionado na sidebar',
        ];
    }
}
