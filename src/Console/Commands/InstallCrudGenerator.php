<?php

namespace Rsk\CrudGenerator\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;
use function Laravel\Prompts\warning;

class InstallCrudGenerator extends Command implements PromptsForMissingInput
{
    protected $signature = 'crud-generator:install
                            {--force : Force overwrite of existing files}';

    protected $description = 'Install the CRUD Generator package';

    /**
     * Required npm dependencies for the CRUD Generator.
     */
    protected array $requiredNpmDependencies = [
        '@tanstack/react-table',
        '@tanstack/react-virtual',
        'fuse.js',
        'usehooks-ts',
    ];

    /**
     * Required shadcn/ui components for the CRUD Generator.
     */
    protected array $requiredShadcnComponents = [
        'alert',
        'alert-dialog',
        'badge',
        'button',
        'card',
        'checkbox',
        'input',
        'label',
        'popover',
        'select',
        'skeleton',
        'switch',
        'table',
        'tooltip',
    ];

    /**
     * Track installation results for final summary.
     */
    protected array $installationResults = [];

    /**
     * Track manual steps needed after installation.
     */
    protected array $manualSteps = [];

    public function handle(): int
    {
        intro('CRUD Generator Installation');

        // Step 1: Check prerequisites
        if (! $this->checkPrerequisites()) {
            error('Prerequisites check failed. Please resolve the issues above and try again.');

            return self::FAILURE;
        }

        // Step 2: Publish assets
        $this->publishAssets();

        // Step 3: Check npm dependencies
        $this->checkNpmDependencies();

        // Step 4: Check shadcn/ui components
        $this->checkShadcnComponents();

        // Step 5: Create routes file if needed
        $this->createRoutesFileIfNeeded();

        // Step 6: Display final summary
        $this->displayFinalSummary();

        return self::SUCCESS;
    }

    /**
     * Check all prerequisites for the CRUD Generator.
     */
    protected function checkPrerequisites(): bool
    {
        $allPassed = true;

        info('Checking prerequisites...');

        // Check Laravel version
        $laravelVersion = $this->getLaravelMajorVersion();
        if ($laravelVersion >= 12) {
            $this->installationResults['Laravel Version'] = "v{$laravelVersion} (OK)";
        } else {
            error("Laravel 12+ is required. Found: v{$laravelVersion}");
            $allPassed = false;
        }

        // Check Inertia.js
        if (class_exists(\Inertia\Inertia::class)) {
            $this->installationResults['Inertia.js'] = 'Installed (OK)';
        } else {
            error('Inertia.js is required but not installed.');
            $this->line('  Install with: composer require inertiajs/inertia-laravel');
            $allPassed = false;
        }

        // Check React starter kit structure
        $pagesPath = resource_path('js/pages');
        if (File::isDirectory($pagesPath)) {
            $this->installationResults['React Structure'] = 'Found (OK)';
        } else {
            error('React starter kit structure not found.');
            $this->line("  Expected directory: {$pagesPath}");
            $this->line('  Please install the Laravel React starter kit first.');
            $allPassed = false;
        }

        // Check shadcn/ui base setup
        $uiComponentsPath = resource_path('js/components/ui');
        if (File::isDirectory($uiComponentsPath)) {
            $this->installationResults['shadcn/ui Setup'] = 'Found (OK)';
        } else {
            warning('shadcn/ui components directory not found.');
            $this->line("  Expected directory: {$uiComponentsPath}");
            $this->line('  Run: npx shadcn@latest init');
            $allPassed = false;
        }

        return $allPassed;
    }

    /**
     * Get the major version of Laravel.
     */
    protected function getLaravelMajorVersion(): int
    {
        $version = \Illuminate\Foundation\Application::VERSION;

        return (int) explode('.', $version)[0];
    }

    /**
     * Publish the package assets.
     */
    protected function publishAssets(): void
    {
        info('Publishing package assets...');

        // Publish config
        $configPublished = spin(
            callback: function () {
                $this->callSilently('vendor:publish', [
                    '--tag' => 'crud-generator-config',
                    '--force' => $this->option('force'),
                ]);

                return true;
            },
            message: 'Publishing configuration...'
        );

        if ($configPublished) {
            $this->installationResults['Config'] = 'Published to config/crud-generator.php';
        }

        // Publish frontend assets
        $assetsPublished = spin(
            callback: function () {
                $this->callSilently('vendor:publish', [
                    '--tag' => 'crud-generator-assets',
                    '--force' => $this->option('force'),
                ]);

                return true;
            },
            message: 'Publishing frontend assets...'
        );

        if ($assetsPublished) {
            $this->installationResults['Frontend Assets'] = 'Published (pages, types, components)';
        }
    }

    /**
     * Check for required npm dependencies.
     */
    protected function checkNpmDependencies(): void
    {
        info('Checking npm dependencies...');

        $packageJsonPath = base_path('package.json');

        if (! File::exists($packageJsonPath)) {
            warning('package.json not found. Cannot check npm dependencies.');
            $this->manualSteps[] = 'Ensure npm dependencies are installed: '.implode(', ', $this->requiredNpmDependencies);

            return;
        }

        $packageJson = json_decode(File::get($packageJsonPath), true);

        if ($packageJson === null) {
            warning('Could not parse package.json.');

            return;
        }

        $installedDeps = array_merge(
            $packageJson['dependencies'] ?? [],
            $packageJson['devDependencies'] ?? []
        );

        $missingDeps = [];
        foreach ($this->requiredNpmDependencies as $dep) {
            if (! isset($installedDeps[$dep])) {
                $missingDeps[] = $dep;
            }
        }

        if (empty($missingDeps)) {
            $this->installationResults['NPM Dependencies'] = 'All required packages found';
        } else {
            $this->installationResults['NPM Dependencies'] = count($missingDeps).' package(s) missing';
            $installCommand = 'bun add '.implode(' ', $missingDeps);
            $this->manualSteps[] = "Install missing npm packages:\n    {$installCommand}";
            warning('Missing npm dependencies detected:');
            foreach ($missingDeps as $dep) {
                $this->line("  - {$dep}");
            }
            $this->newLine();
            $this->line("  Install with: {$installCommand}");
        }
    }

    /**
     * Check for required shadcn/ui components.
     */
    protected function checkShadcnComponents(): void
    {
        info('Checking shadcn/ui components...');

        $uiPath = resource_path('js/components/ui');

        if (! File::isDirectory($uiPath)) {
            warning('shadcn/ui components directory not found.');
            $this->manualSteps[] = 'Initialize shadcn/ui: npx shadcn@latest init';
            $this->manualSteps[] = 'Install components: npx shadcn@latest add '.implode(' ', $this->requiredShadcnComponents);

            return;
        }

        $missingComponents = [];
        foreach ($this->requiredShadcnComponents as $component) {
            $componentPath = "{$uiPath}/{$component}.tsx";
            if (! File::exists($componentPath)) {
                $missingComponents[] = $component;
            }
        }

        if (empty($missingComponents)) {
            $this->installationResults['shadcn/ui Components'] = 'All required components found';
        } else {
            $this->installationResults['shadcn/ui Components'] = count($missingComponents).' component(s) missing';
            $installCommand = 'npx shadcn@latest add '.implode(' ', $missingComponents);
            $this->manualSteps[] = "Install missing shadcn/ui components:\n    {$installCommand}";
            warning('Missing shadcn/ui components detected:');
            foreach ($missingComponents as $component) {
                $this->line("  - {$component}");
            }
            $this->newLine();
            $this->line("  Install with: {$installCommand}");
        }
    }

    /**
     * Create the routes file if it doesn't exist.
     */
    protected function createRoutesFileIfNeeded(): void
    {
        info('Checking routes file...');

        $routesFile = config('crud-generator.routes_file', 'routes/rsk-crud.php');
        $routesPath = base_path($routesFile);
        $routesMarker = config('crud-generator.routes_marker', '// [RSK-CRUD-ROUTES]');

        if (File::exists($routesPath)) {
            $this->installationResults['Routes File'] = "Exists ({$routesFile})";

            return;
        }

        $routesContent = $this->generateRoutesFileContent($routesMarker);

        spin(
            callback: function () use ($routesPath, $routesContent) {
                File::ensureDirectoryExists(dirname($routesPath));
                File::put($routesPath, $routesContent);

                return true;
            },
            message: 'Creating routes file...'
        );

        $this->installationResults['Routes File'] = "Created ({$routesFile})";
        $this->manualSteps[] = "Include the routes file in routes/web.php:\n    require __DIR__.'/".basename($routesFile)."';";
    }

    /**
     * Generate the routes file content.
     */
    protected function generateRoutesFileContent(string $marker): string
    {
        return <<<PHP
<?php
/**
 * RSK CRUD Routes - React Starter Kit CRUD Generator
 *
 * This file contains routes generated automatically by the CRUD Generator.
 * DO NOT EDIT MANUALLY - routes are managed by the generator.
 *
 * Routes below are inserted automatically when you generate a new CRUD
 * with the "Add routes" option enabled.
 */

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    {$marker}
});

PHP;
    }

    /**
     * Display the final installation summary.
     */
    protected function displayFinalSummary(): void
    {
        $this->newLine();

        // Display installation results table
        $tableData = [];
        foreach ($this->installationResults as $item => $status) {
            $tableData[] = [$item, $status];
        }

        table(
            headers: ['Component', 'Status'],
            rows: $tableData
        );

        // Display manual steps if any
        if (! empty($this->manualSteps)) {
            $this->newLine();
            warning('Manual steps required:');
            $this->newLine();
            foreach ($this->manualSteps as $index => $step) {
                $stepNumber = $index + 1;
                $this->line("  {$stepNumber}. {$step}");
                $this->newLine();
            }
        }

        // Display access URL
        $this->newLine();
        $routePrefix = config('crud-generator.route_prefix', 'admin/crud-generator');

        try {
            $url = url($routePrefix);
        } catch (\Exception $e) {
            $url = "/{$routePrefix}";
        }

        outro("Installation complete! Access the CRUD Generator at: {$url}");

        if (! empty($this->manualSteps)) {
            $this->newLine();
            info('After completing the manual steps above, run: bun run build');
        }
    }
}
