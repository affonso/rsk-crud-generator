<?php

declare(strict_types=1);

namespace Rsk\CrudGenerator\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Rsk\CrudGenerator\Contracts\ConfigurationManagerInterface;
use Rsk\CrudGenerator\Contracts\ModelIntrospectorInterface;
use Rsk\CrudGenerator\Contracts\RelationshipDetectorInterface;
use Rsk\CrudGenerator\Contracts\TypeMapperInterface;

/**
 * Controller for the CRUD Generator wizard UI.
 *
 * Provides endpoints for model discovery, configuration, and CRUD generation.
 * Delegates domain logic to specialized services via constructor injection.
 */
class CrudGeneratorController extends Controller
{
    public function __construct(
        private ModelIntrospectorInterface $modelIntrospector,
        private RelationshipDetectorInterface $relationshipDetector,
        private TypeMapperInterface $typeMapper,
        private ConfigurationManagerInterface $configManager,
    ) {}

    /**
     * Display the CRUD Generator wizard.
     */
    public function index(): Response
    {
        return Inertia::render('Admin/CrudGenerator/Index', [
            'models' => $this->modelIntrospector->getAvailableModels(),
        ]);
    }

    /**
     * Get configuration for a specific model.
     *
     * Returns model schema, fields, and relationship information
     * for the CRUD Generator wizard.
     */
    public function getModelConfig(string $model): JsonResponse
    {
        $modelClass = config('crud-generator.models_namespace', 'App\\Models').'\\'.Str::studly($model);

        if (! class_exists($modelClass)) {
            return response()->json([
                'error' => "Model {$model} não encontrado.",
            ], 404);
        }

        // Get model schema (table, columns, fillable)
        $schema = $this->modelIntrospector->getModelSchema($modelClass);

        // Detect relationships using reflection
        $instance = new $modelClass;
        $relationships = $this->relationshipDetector->detectRelationships($instance, $modelClass);

        // Create map of foreign keys to relationship metadata
        $fkToRelationship = $this->relationshipDetector->mapForeignKeysToRelationships($relationships);

        // Build field configurations
        $fields = $this->modelIntrospector->buildFieldConfigurations(
            $schema['table'],
            $schema['fillable'],
            $fkToRelationship,
            $this->typeMapper
        );

        return response()->json([
            'model' => $model,
            'modelStudly' => Str::studly($model),
            'table' => $schema['table'],
            'fields' => $fields,
            'relationships' => $relationships,
        ]);
    }

    /**
     * Generate CRUD files for the specified model.
     *
     * Orchestrates the generation process by:
     * 1. Validating input
     * 2. Saving field configuration
     * 3. Running the artisan command
     * 4. Optionally adding routes and navigation items
     */
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'model' => ['required', 'string'],
            'fields' => ['required', 'array'],
            'fields.*.name' => ['required', 'string'],
            'fields.*.label' => ['required', 'string'],
            'fields.*.inputType' => ['required', 'string'],
            'fields.*.required' => ['required', 'boolean'],
            'fields.*.validation' => ['nullable', 'string'],
            'options' => ['nullable', 'array'],
            'options.force' => ['nullable', 'boolean'],
            'options.withRequests' => ['nullable', 'boolean'],
            'options.addRoutes' => ['nullable', 'boolean'],
            'options.addNavItem' => ['nullable', 'boolean'],
            'options.navIcon' => ['nullable', 'string'],
        ]);

        $model = Str::studly($validated['model']);
        $modelClass = config('crud-generator.models_namespace', 'App\\Models').'\\'.$model;

        if (! class_exists($modelClass)) {
            return response()->json([
                'success' => false,
                'message' => "Model {$model} não encontrado.",
            ], 404);
        }

        // Store field configuration for the command to use
        $configPath = storage_path("app/crud-generator/{$model}.json");
        File::ensureDirectoryExists(dirname($configPath));
        File::put($configPath, json_encode($validated['fields'], JSON_PRETTY_PRINT));

        $options = [
            'model' => $model,
            '--force' => $validated['options']['force'] ?? false,
            '--with-requests' => $validated['options']['withRequests'] ?? false,
            '--config-file' => $configPath,
        ];

        try {
            $exitCode = Artisan::call('make:shadcn-crud', $options);
            $output = Artisan::output();

            // Clean up config file
            File::delete($configPath);

            if ($exitCode === 0) {
                $messages = ["CRUD para {$model} gerado com sucesso!"];

                // Add routes if requested
                if ($validated['options']['addRoutes'] ?? false) {
                    $routeResult = $this->configManager->addCrudRoutes($model);
                    $messages[] = $routeResult['message'];
                }

                // Add nav item if requested
                if ($validated['options']['addNavItem'] ?? false) {
                    $navIcon = $validated['options']['navIcon'] ?? 'Database';
                    $navResult = $this->configManager->addNavigationItem($model, $navIcon);
                    $messages[] = $navResult['message'];
                }

                return response()->json([
                    'success' => true,
                    'message' => implode("\n", $messages),
                    'output' => $output,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar CRUD.',
                'output' => $output,
            ], 500);
        } catch (\Exception $e) {
            File::delete($configPath);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
