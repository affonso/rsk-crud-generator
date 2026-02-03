<?php

namespace Rsk\CrudGenerator;

use Illuminate\Support\ServiceProvider;
use Rsk\CrudGenerator\Console\Commands\InstallCrudGenerator;
use Rsk\CrudGenerator\Console\Commands\MakeShadcnCrud;
use Rsk\CrudGenerator\Contracts\ConfigurationManagerInterface;
use Rsk\CrudGenerator\Contracts\ModelIntrospectorInterface;
use Rsk\CrudGenerator\Contracts\RelationshipDetectorInterface;
use Rsk\CrudGenerator\Contracts\TypeMapperInterface;
use Rsk\CrudGenerator\Services\ConfigurationManager;
use Rsk\CrudGenerator\Services\ModelIntrospector;
use Rsk\CrudGenerator\Services\RelationshipDetector;
use Rsk\CrudGenerator\Services\TypeMapper;

class CrudGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/crud-generator.php',
            'crud-generator'
        );

        // Bind TypeMapper first (no dependencies)
        $this->app->singleton(TypeMapperInterface::class, TypeMapper::class);

        // Bind ModelIntrospector (depends on TypeMapper)
        $this->app->singleton(ModelIntrospectorInterface::class, ModelIntrospector::class);

        // Bind RelationshipDetector (depends on ModelIntrospector)
        $this->app->singleton(RelationshipDetectorInterface::class, RelationshipDetector::class);

        // Bind ConfigurationManager (no dependencies)
        $this->app->singleton(ConfigurationManagerInterface::class, ConfigurationManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Only load routes in local/development environments (dev-only package)
        if ($this->app->environment('local', 'development')) {
            $this->loadRoutesFrom(__DIR__.'/../routes/crud-generator.php');
        }

        if ($this->app->runningInConsole()) {
            $this->registerCommands();
            $this->registerPublishables();
        }
    }

    /**
     * Register the package's Artisan commands.
     */
    protected function registerCommands(): void
    {
        $this->commands([
            InstallCrudGenerator::class,
            MakeShadcnCrud::class,
        ]);
    }

    /**
     * Register the package's publishable resources.
     */
    protected function registerPublishables(): void
    {
        // Publish config file
        $this->publishes([
            __DIR__.'/../config/crud-generator.php' => config_path('crud-generator.php'),
        ], 'crud-generator-config');

        // Publish stubs
        $this->publishes([
            __DIR__.'/../stubs/shadcn-crud' => base_path('stubs/vendor/crud-generator'),
        ], 'crud-generator-stubs');

        // Publish frontend assets (JS pages, types, components)
        $this->publishes([
            __DIR__.'/../resources/js/pages/CrudGenerator' => resource_path('js/pages/CrudGenerator'),
            __DIR__.'/../resources/js/pages/CrudManager' => resource_path('js/pages/CrudManager'),
            __DIR__.'/../resources/js/types/crud-generator.ts' => resource_path('js/types/crud-generator.ts'),
            __DIR__.'/../resources/js/components/ui/icon-picker.tsx' => resource_path('js/components/ui/icon-picker.tsx'),
            __DIR__.'/../resources/js/components/ui/icons-data.ts' => resource_path('js/components/ui/icons-data.ts'),
        ], 'crud-generator-assets');
    }
}
