<?php

namespace Rsk\CrudGenerator\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;

class MakeShadcnCrud extends Command
{
    protected $signature = 'make:shadcn-crud
                            {model : O nome do Model (ex: Post, User)}
                            {--force : Sobrescrever arquivos existentes}
                            {--api : Gerar também rotas API}
                            {--with-requests : Gerar Form Requests para validação}
                            {--config-file= : Arquivo JSON com configuração customizada dos campos}';

    protected $description = 'Gera Controller, Views React/Shadcn e rotas para um Model existente';

    protected string $model;

    protected string $modelStudly;

    protected string $modelCamel;

    protected string $modelPlural;

    protected string $modelPluralStudly;

    protected array $columns = [];

    protected array $columnTypes = [];

    protected array $fillable = [];

    protected array $customConfig = [];

    protected string $stubPath;

    protected array $relationships = [];

    protected string $modelClass;

    public function handle(): int
    {
        $this->model = $this->argument('model');
        $this->modelStudly = Str::studly($this->model);
        $this->modelCamel = Str::camel($this->model);
        $this->modelPlural = Str::plural(Str::snake($this->model));
        $this->modelPluralStudly = Str::studly($this->modelPlural);

        $modelClass = 'App\\Models\\'.$this->modelStudly;

        // Verificar se Model existe
        if (! class_exists($modelClass)) {
            $this->error("Model {$modelClass} não encontrado!");
            $this->line("Crie o model primeiro: php artisan make:model {$this->modelStudly} -m");

            return 1;
        }

        // Load custom config if provided
        $configFile = $this->option('config-file');
        if ($configFile && File::exists($configFile)) {
            $this->customConfig = json_decode(File::get($configFile), true) ?? [];
        }

        // Obter informações do Model
        $this->modelClass = $modelClass;
        $instance = new $modelClass;
        $table = $instance->getTable();
        $this->fillable = $instance->getFillable();
        $this->columns = Schema::getColumnListing($table);
        $this->columnTypes = $this->getColumnTypes($table);

        if (empty($this->fillable)) {
            $this->warn('Model não tem $fillable definido. Usando todas as colunas exceto id, timestamps.');
            $this->fillable = array_diff($this->columns, ['id', 'created_at', 'updated_at', 'deleted_at']);
        }

        // Detectar relacionamentos do Model
        $this->relationships = $this->detectRelationships($instance);

        // Definir path dos stubs
        $this->stubPath = $this->getStubPath();

        // Gerar arquivos
        $this->info("Gerando CRUD para {$this->modelStudly}...\n");

        $this->generateController();

        if ($this->option('with-requests')) {
            $this->generateFormRequests();
        }

        $this->generateTypesFile();
        $this->generateIndexPage();
        $this->generateFormDialog();
        $this->generateColumns();
        $this->generateDataTable();
        $this->showRoutes();

        $this->newLine();
        $this->info("CRUD para {$this->modelStudly} criado com sucesso!");

        return 0;
    }

    protected function getStubPath(): string
    {
        // Primeiro verifica se existem stubs publicados no projeto Laravel
        $publishedPath = base_path('stubs/shadcn-crud');
        if (File::isDirectory($publishedPath)) {
            return $publishedPath;
        }

        // Fallback para stubs do pacote
        return __DIR__.'/../../../stubs/shadcn-crud';
    }

    protected function getColumnTypes(string $table): array
    {
        $types = [];
        foreach ($this->columns as $column) {
            $type = Schema::getColumnType($table, $column);
            $types[$column] = $this->mapColumnType($type);
        }

        return $types;
    }

    protected function mapColumnType(string $dbType): array
    {
        return match ($dbType) {
            'integer', 'bigint', 'smallint' => ['type' => 'number', 'input' => 'number'],
            'decimal', 'float', 'double' => ['type' => 'number', 'input' => 'number', 'step' => '0.01'],
            'boolean' => ['type' => 'boolean', 'input' => 'checkbox'],
            'date' => ['type' => 'string', 'input' => 'date'],
            'datetime', 'timestamp' => ['type' => 'string', 'input' => 'datetime-local'],
            'time' => ['type' => 'string', 'input' => 'time'],
            'text', 'longtext', 'mediumtext' => ['type' => 'string', 'input' => 'textarea'],
            'json' => ['type' => 'object', 'input' => 'textarea'],
            default => ['type' => 'string', 'input' => 'text'],
        };
    }

    /**
     * Detecta relacionamentos definidos no Model usando Reflection.
     */
    protected function detectRelationships(object $instance): array
    {
        $relationships = [
            'belongsTo' => [],
            'hasMany' => [],
            'belongsToMany' => [],
        ];

        $reflection = new ReflectionClass($instance);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            // Ignorar métodos herdados e com parâmetros obrigatórios
            if ($method->class !== $this->modelClass) {
                continue;
            }
            if ($method->getNumberOfRequiredParameters() > 0) {
                continue;
            }

            try {
                $result = $method->invoke($instance);

                if ($result instanceof BelongsTo) {
                    $relatedModel = class_basename($result->getRelated());
                    $relatedTable = $result->getRelated()->getTable();

                    $relationships['belongsTo'][] = [
                        'method' => $method->getName(),
                        'foreignKey' => $result->getForeignKeyName(),
                        'relatedModel' => $relatedModel,
                        'relatedModelClass' => get_class($result->getRelated()),
                        'relatedTable' => $relatedTable,
                        'displayField' => $this->guessDisplayField($relatedTable),
                    ];
                } elseif ($result instanceof HasMany) {
                    $relationships['hasMany'][] = [
                        'method' => $method->getName(),
                        'relatedModel' => class_basename($result->getRelated()),
                        'foreignKey' => $result->getForeignKeyName(),
                    ];
                } elseif ($result instanceof BelongsToMany) {
                    $relationships['belongsToMany'][] = [
                        'method' => $method->getName(),
                        'relatedModel' => class_basename($result->getRelated()),
                        'pivotTable' => $result->getTable(),
                    ];
                }
            } catch (\Throwable $e) {
                // Ignorar métodos que lançam exceções
                continue;
            }
        }

        return $relationships;
    }

    /**
     * Tenta adivinhar o campo de exibição mais apropriado para uma tabela.
     */
    protected function guessDisplayField(string $table): string
    {
        $commonNames = ['nome', 'name', 'title', 'titulo', 'label', 'description', 'descricao', 'sigla'];
        $columns = Schema::getColumnListing($table);

        foreach ($commonNames as $name) {
            if (in_array($name, $columns)) {
                return $name;
            }
        }

        return 'id';
    }

    /**
     * Verifica se um campo é uma foreign key de relacionamento BelongsTo.
     */
    protected function getRelationshipForField(string $field): ?array
    {
        foreach ($this->relationships['belongsTo'] as $rel) {
            if ($rel['foreignKey'] === $field) {
                return $rel;
            }
        }

        return null;
    }

    protected function getFieldConfig(string $field): array
    {
        // Primeiro verificar se é uma FK de relacionamento BelongsTo
        $relationship = $this->getRelationshipForField($field);

        // Check if we have custom config for this field
        foreach ($this->customConfig as $config) {
            if ($config['name'] === $field) {
                $result = [
                    'label' => $config['label'] ?? Str::title(str_replace('_', ' ', $field)),
                    'input' => $config['inputType'] ?? 'text',
                    'type' => $config['tsType'] ?? 'string',
                    'required' => $config['required'] ?? true,
                    'validation' => $config['validation'] ?? 'required|string|max:255',
                    'isRelationship' => $config['isRelationship'] ?? false,
                    'relationshipType' => $config['relationshipType'] ?? null,
                    'relationshipMethod' => $config['relationshipMethod'] ?? null,
                    'relatedModel' => $config['relatedModel'] ?? null,
                    'relatedTable' => $config['relatedTable'] ?? null,
                    'displayField' => $config['displayField'] ?? null,
                ];

                // Se é um campo de relacionamento mas customConfig não tem os dados completos,
                // preencher automaticamente a partir da detecção
                if ($relationship && ($result['input'] === 'relationship-select' || $result['isRelationship'])) {
                    $result['isRelationship'] = true;
                    $result['relationshipType'] = $result['relationshipType'] ?? 'belongsTo';
                    $result['relationshipMethod'] = $result['relationshipMethod'] ?? $relationship['method'];
                    $result['relatedModel'] = $result['relatedModel'] ?? $relationship['relatedModel'];
                    $result['relatedTable'] = $result['relatedTable'] ?? $relationship['relatedTable'];
                    $result['displayField'] = $result['displayField'] ?? $relationship['displayField'];
                }

                return $result;
            }
        }

        // Check if this field is a BelongsTo foreign key
        if ($relationship) {
            return [
                'label' => Str::title(str_replace('_', ' ', $relationship['method'])),
                'input' => 'relationship-select',
                'type' => 'number',
                'required' => true,
                'validation' => 'required|exists:'.$relationship['relatedTable'].',id',
                'isRelationship' => true,
                'relationshipType' => 'belongsTo',
                'relationshipMethod' => $relationship['method'],
                'relatedModel' => $relationship['relatedModel'],
                'relatedTable' => $relationship['relatedTable'],
                'displayField' => $relationship['displayField'],
            ];
        }

        // Fall back to auto-detected config
        $type = $this->columnTypes[$field] ?? ['type' => 'string', 'input' => 'text'];

        return [
            'label' => Str::title(str_replace('_', ' ', $field)),
            'input' => $type['input'],
            'type' => $type['type'],
            'required' => true,
            'validation' => $this->getDefaultValidation($type['input']),
            'isRelationship' => false,
        ];
    }

    protected function getDefaultValidation(string $inputType): string
    {
        return match ($inputType) {
            'number' => 'required|numeric',
            'checkbox' => 'boolean',
            'date', 'datetime-local' => 'required|date',
            'email' => 'required|email',
            'textarea' => 'nullable|string',
            default => 'required|string|max:255',
        };
    }

    protected function generateController(): void
    {
        $stub = $this->getStub('controller.stub');

        $validationRules = $this->generateValidationRules();
        $relationshipPlaceholders = $this->generateRelationshipPlaceholders();

        $content = $this->replacePlaceholders($stub, array_merge([
            '{{ validationRules }}' => $validationRules,
            '{{ fillableArray }}' => $this->generateFillableArray(),
        ], $relationshipPlaceholders));

        $path = app_path("Http/Controllers/{$this->modelStudly}Controller.php");

        if (File::exists($path) && ! $this->option('force')) {
            if (! $this->confirm('Controller já existe. Sobrescrever?')) {
                $this->line('  Controller ignorado');

                return;
            }
        }

        File::put($path, $content);
        $this->line("  Controller: app/Http/Controllers/{$this->modelStudly}Controller.php");
    }

    protected function generateFormRequests(): void
    {
        $stub = $this->getStub('form-request.stub');

        $content = $this->replacePlaceholders($stub, [
            '{{ validationRules }}' => $this->generateValidationRulesArray(),
        ]);

        $dir = app_path("Http/Requests/{$this->modelStudly}");
        File::ensureDirectoryExists($dir);

        // Store Request
        $storePath = "{$dir}/Store{$this->modelStudly}Request.php";
        $storeContent = str_replace('{{ requestType }}', 'Store', $content);
        File::put($storePath, $storeContent);
        $this->line("  Request: app/Http/Requests/{$this->modelStudly}/Store{$this->modelStudly}Request.php");

        // Update Request
        $updatePath = "{$dir}/Update{$this->modelStudly}Request.php";
        $updateContent = str_replace('{{ requestType }}', 'Update', $content);
        File::put($updatePath, $updateContent);
        $this->line("  Request: app/Http/Requests/{$this->modelStudly}/Update{$this->modelStudly}Request.php");
    }

    protected function generateTypesFile(): void
    {
        $stub = $this->getStub('types.stub');

        $interfaceFields = $this->generateTypeScriptInterface();
        $relationshipPlaceholders = $this->generateRelationshipPlaceholders();
        $relationshipInterfaceFields = $this->generateRelationshipInterfaceFields();

        $content = $this->replacePlaceholders($stub, array_merge([
            '{{ interfaceFields }}' => $interfaceFields,
            '{{ relationshipInterfaceFields }}' => $relationshipInterfaceFields,
        ], $relationshipPlaceholders));

        // Limpar linhas vazias extras que podem ser deixadas pelos placeholders vazios
        $content = preg_replace('/\n{3,}/', "\n\n", $content);

        $dir = resource_path('js/types/models');
        File::ensureDirectoryExists($dir);

        $path = "{$dir}/{$this->modelCamel}.ts";
        File::put($path, $content);
        $this->line("  Types: resources/js/types/models/{$this->modelCamel}.ts");
    }

    /**
     * Gera campos de interface TypeScript para relacionamentos.
     */
    protected function generateRelationshipInterfaceFields(): string
    {
        $fields = [];

        // BelongsTo - objeto opcional
        foreach ($this->relationships['belongsTo'] as $rel) {
            $fields[] = "    {$rel['method']}?: {$rel['relatedModel']};";
        }

        // HasMany - contagem
        foreach ($this->relationships['hasMany'] as $rel) {
            $fields[] = "    {$rel['method']}_count?: number;";
        }

        return empty($fields) ? '' : "\n    // Relacionamentos\n".implode("\n", $fields);
    }

    protected function generateIndexPage(): void
    {
        $stub = $this->getStub('index-page.stub');
        $relationshipPlaceholders = $this->generateRelationshipPlaceholders();

        $content = $this->replacePlaceholders($stub, $relationshipPlaceholders);

        $dir = resource_path("js/pages/{$this->modelPluralStudly}");
        File::ensureDirectoryExists($dir);

        $path = "{$dir}/Index.tsx";

        if (File::exists($path) && ! $this->option('force')) {
            if (! $this->confirm('Index page já existe. Sobrescrever?')) {
                $this->line('  Index page ignorada');

                return;
            }
        }

        File::put($path, $content);
        $this->line("  Page: resources/js/pages/{$this->modelPluralStudly}/Index.tsx");
    }

    protected function generateFormDialog(): void
    {
        $stub = $this->getStub('form-dialog.stub');

        $formFields = $this->generateFormFields();
        $defaultValues = $this->generateDefaultValues();
        $relationshipPlaceholders = $this->generateRelationshipPlaceholders();

        // Verificar se precisa importar Select components
        $needsSelectImport = $this->hasBelongsToRelationships();

        $content = $this->replacePlaceholders($stub, array_merge([
            '{{ formFields }}' => $formFields,
            '{{ defaultValues }}' => $defaultValues,
            '{{ selectImports }}' => $needsSelectImport ? $this->getSelectImports() : '',
            '{{ relationshipInterfaceFields }}' => '',  // FormDialog não precisa disso
        ], $relationshipPlaceholders));

        $dir = resource_path("js/pages/{$this->modelPluralStudly}");
        File::ensureDirectoryExists($dir);

        $path = "{$dir}/FormDialog.tsx";
        File::put($path, $content);
        $this->line("  Component: resources/js/pages/{$this->modelPluralStudly}/FormDialog.tsx");
    }

    protected function getSelectImports(): string
    {
        return <<<'TSX'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
TSX;
    }

    protected function generateColumns(): void
    {
        $stub = $this->getStub('columns.stub');

        $columnDefinitions = $this->generateColumnDefinitions();

        $content = $this->replacePlaceholders($stub, [
            '{{ columnDefinitions }}' => $columnDefinitions,
        ]);

        $dir = resource_path("js/pages/{$this->modelPluralStudly}");
        $path = "{$dir}/columns.tsx";
        File::put($path, $content);
        $this->line("  Columns: resources/js/pages/{$this->modelPluralStudly}/columns.tsx");
    }

    protected function generateDataTable(): void
    {
        $stub = $this->getStub('data-table.stub');
        $content = $this->replacePlaceholders($stub);

        $dir = resource_path("js/pages/{$this->modelPluralStudly}");
        $path = "{$dir}/DataTable.tsx";
        File::put($path, $content);
        $this->line("  DataTable: resources/js/pages/{$this->modelPluralStudly}/DataTable.tsx");
    }

    protected function showRoutes(): void
    {
        $this->newLine();
        $this->info('Adicione estas rotas em routes/web.php:');
        $this->newLine();

        $routes = <<<ROUTES
use App\Http\Controllers\\{$this->modelStudly}Controller;

Route::resource('{$this->modelPlural}', {$this->modelStudly}Controller::class)
    ->only(['index', 'store', 'update', 'destroy']);
ROUTES;

        $this->line($routes);

        if ($this->option('api')) {
            $this->newLine();
            $this->info('Rotas API (routes/api.php):');
            $this->newLine();

            $apiRoutes = <<<ROUTES
Route::apiResource('{$this->modelPlural}', {$this->modelStudly}Controller::class);
ROUTES;
            $this->line($apiRoutes);
        }
    }

    // ==================== RELATIONSHIP PLACEHOLDER METHODS ====================

    /**
     * Gera todos os placeholders relacionados a relacionamentos.
     */
    protected function generateRelationshipPlaceholders(): array
    {
        $imports = [];
        $eagerLoad = [];
        $dataProps = [];
        $propsInterface = [];
        $propsDestructure = [];
        $propsPassthrough = [];
        $typeImports = [];

        foreach ($this->relationships['belongsTo'] as $rel) {
            $relatedModel = $rel['relatedModel'];
            $relatedModelCamel = Str::camel($relatedModel);
            $relatedModelPlural = Str::plural($relatedModelCamel);
            $displayField = $rel['displayField'];

            // PHP imports
            $imports[] = "use App\\Models\\{$relatedModel};";

            // Eager loading
            $eagerLoad[] = "'{$rel['method']}'";

            // Props de dados para a view (opções do select)
            $dataProps[] = "            '{$relatedModelPlural}' => {$relatedModel}::all(['id', '{$displayField}']),";

            // Interface TypeScript para props
            $propsInterface[] = "    {$relatedModelPlural}: {$relatedModel}[];";

            // Destructure props no componente
            $propsDestructure[] = $relatedModelPlural;

            // Passar props para FormDialog
            $propsPassthrough[] = "                {$relatedModelPlural}={{$relatedModelPlural}}";

            // Imports de tipos TypeScript
            $typeImports[] = "import type { {$relatedModel} } from '@/types/models/{$relatedModelCamel}';";
        }

        // HasMany - adicionar withCount
        $withCountMethods = [];
        foreach ($this->relationships['hasMany'] as $rel) {
            $withCountMethods[] = "'{$rel['method']}'";
        }

        return [
            '{{ relationshipImports }}' => implode("\n", array_unique($imports)),
            '{{ eagerLoadClause }}' => empty($eagerLoad) ? '' : 'with(['.implode(', ', $eagerLoad).'])->',
            '{{ withCountClause }}' => empty($withCountMethods) ? '' : 'withCount(['.implode(', ', $withCountMethods).'])->',
            '{{ relationshipDataProps }}' => implode("\n", $dataProps),
            '{{ relationshipPropsInterface }}' => implode("\n", $propsInterface),
            '{{ relationshipPropsDestructure }}' => empty($propsDestructure) ? '' : ', '.implode(', ', $propsDestructure),
            '{{ relationshipPropsPassthrough }}' => implode("\n", $propsPassthrough),
            '{{ relationshipTypeImports }}' => implode("\n", array_unique($typeImports)),
        ];
    }

    /**
     * Verifica se o model tem relacionamentos BelongsTo.
     */
    protected function hasBelongsToRelationships(): bool
    {
        return ! empty($this->relationships['belongsTo']);
    }

    /**
     * Verifica se o model tem relacionamentos HasMany.
     */
    protected function hasHasManyRelationships(): bool
    {
        return ! empty($this->relationships['hasMany']);
    }

    // ==================== HELPER METHODS ====================

    protected function getStub(string $name): string
    {
        $path = "{$this->stubPath}/{$name}";

        if (! File::exists($path)) {
            throw new \RuntimeException("Stub não encontrado: {$path}");
        }

        return File::get($path);
    }

    protected function replacePlaceholders(string $content, array $extra = []): string
    {
        $replacements = [
            '{{ model }}' => $this->model,
            '{{ modelStudly }}' => $this->modelStudly,
            '{{ modelCamel }}' => $this->modelCamel,
            '{{ modelPlural }}' => $this->modelPlural,
            '{{ modelPluralStudly }}' => $this->modelPluralStudly,
            '{{ modelTitle }}' => Str::title(str_replace('_', ' ', $this->model)),
            '{{ modelPluralTitle }}' => Str::title(str_replace('_', ' ', $this->modelPlural)),
        ];

        $replacements = array_merge($replacements, $extra);

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    protected function generateValidationRules(): string
    {
        $rules = [];
        foreach ($this->fillable as $field) {
            $config = $this->getFieldConfig($field);
            $rules[] = "            '{$field}' => '{$config['validation']}'";
        }

        return implode(",\n", $rules);
    }

    protected function generateValidationRulesArray(): string
    {
        $rules = [];
        foreach ($this->fillable as $field) {
            $config = $this->getFieldConfig($field);
            $validationParts = explode('|', $config['validation']);
            $arrayParts = array_map(fn ($p) => "'{$p}'", $validationParts);
            $rules[] = "            '{$field}' => [".implode(', ', $arrayParts).']';
        }

        return implode(",\n", $rules);
    }

    protected function generateFillableArray(): string
    {
        return "['".implode("', '", $this->fillable)."']";
    }

    protected function generateTypeScriptInterface(): string
    {
        $fields = ['    id: number;'];

        foreach ($this->columns as $column) {
            if ($column === 'id') {
                continue;
            }

            $config = $this->getFieldConfig($column);
            $nullable = in_array($column, ['deleted_at']) ? ' | null' : '';
            $fields[] = "    {$column}: {$config['type']}{$nullable};";
        }

        return implode("\n", $fields);
    }

    protected function generateFormFields(): string
    {
        $fields = [];

        foreach ($this->fillable as $field) {
            $config = $this->getFieldConfig($field);
            $fields[] = $this->getFieldComponent($field, $config['label'], $config);
        }

        return implode("\n\n", $fields);
    }

    protected function getFieldComponent(string $field, string $label, array $config): string
    {
        $input = $config['input'];

        // Componente Select para relacionamentos BelongsTo
        if ($input === 'relationship-select') {
            // Tentar obter relatedModel do config ou da detecção automática
            $relatedModel = $config['relatedModel'] ?? null;
            $displayField = $config['displayField'] ?? 'id';

            // Se não temos relatedModel no config, tentar detectar
            if (! $relatedModel) {
                $relationship = $this->getRelationshipForField($field);
                if ($relationship) {
                    $relatedModel = $relationship['relatedModel'];
                    $displayField = $relationship['displayField'] ?? 'id';
                }
            }

            // Se temos relatedModel, gerar Select
            if ($relatedModel) {
                $relatedModelPlural = Str::plural(Str::camel($relatedModel));

                return <<<TSX
                <div className="space-y-2">
                    <Label htmlFor="{$field}">{$label}</Label>
                    <Select
                        value={data.{$field}?.toString() ?? ''}
                        onValueChange={(value) => setData('{$field}', parseInt(value))}
                    >
                        <SelectTrigger id="{$field}" className={errors.{$field} ? 'border-red-500' : ''}>
                            <SelectValue placeholder="Selecione..." />
                        </SelectTrigger>
                        <SelectContent>
                            {{$relatedModelPlural}.map((item) => (
                                <SelectItem key={item.id} value={item.id.toString()}>
                                    {item.{$displayField}}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    {errors.{$field} && <p className="text-sm text-red-500">{errors.{$field}}</p>}
                </div>
TSX;
            }
            // Se não temos relatedModel, vai cair no fallback do Input normal abaixo
        }

        if ($input === 'textarea') {
            return <<<TSX
                <div className="space-y-2">
                    <Label htmlFor="{$field}">{$label}</Label>
                    <Textarea
                        id="{$field}"
                        value={data.{$field}}
                        onChange={(e) => setData('{$field}', e.target.value)}
                        className={errors.{$field} ? 'border-red-500' : ''}
                    />
                    {errors.{$field} && <p className="text-sm text-red-500">{errors.{$field}}</p>}
                </div>
TSX;
        }

        if ($input === 'checkbox') {
            return <<<TSX
                <div className="flex items-center space-x-2">
                    <Checkbox
                        id="{$field}"
                        checked={data.{$field}}
                        onCheckedChange={(checked) => setData('{$field}', checked)}
                    />
                    <Label htmlFor="{$field}">{$label}</Label>
                    {errors.{$field} && <p className="text-sm text-red-500">{errors.{$field}}</p>}
                </div>
TSX;
        }

        $step = isset($config['step']) ? " step=\"{$config['step']}\"" : '';

        return <<<TSX
                <div className="space-y-2">
                    <Label htmlFor="{$field}">{$label}</Label>
                    <Input
                        id="{$field}"
                        type="{$input}"{$step}
                        value={data.{$field}}
                        onChange={(e) => setData('{$field}', e.target.value)}
                        className={errors.{$field} ? 'border-red-500' : ''}
                    />
                    {errors.{$field} && <p className="text-sm text-red-500">{errors.{$field}}</p>}
                </div>
TSX;
    }

    protected function generateDefaultValues(): string
    {
        $defaults = [];

        foreach ($this->fillable as $field) {
            $config = $this->getFieldConfig($field);
            $default = match ($config['type']) {
                'number' => '0',
                'boolean' => 'false',
                default => "''",
            };
            $defaults[] = "        {$field}: item?.{$field} ?? {$default}";
        }

        return implode(",\n", $defaults);
    }

    protected function generateColumnDefinitions(): string
    {
        $columns = [];
        $visibleColumns = array_diff($this->columns, ['password', 'remember_token', 'deleted_at']);

        foreach ($visibleColumns as $column) {
            $config = $this->getFieldConfig($column);
            $header = $config['label'];

            // Para campos de relacionamento BelongsTo, usar o accessor do relacionamento
            if (! empty($config['isRelationship']) && $config['relationshipType'] === 'belongsTo') {
                $relationshipMethod = $config['relationshipMethod'];
                $displayField = $config['displayField'];

                $columns[] = <<<TSX
    {
        accessorKey: '{$relationshipMethod}',
        header: ({ column }) => (
            <Button
                variant="ghost"
                onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}
            >
                {$header}
                <ArrowUpDown className="ml-2 h-4 w-4" />
            </Button>
        ),
        cell: ({ row }) => row.original.{$relationshipMethod}?.{$displayField} ?? '-',
    }
TSX;

                continue;
            }

            $cell = match ($column) {
                'id' => "row.getValue('id')",
                'created_at', 'updated_at' => "new Date(row.getValue('{$column}')).toLocaleDateString('pt-BR')",
                default => $config['type'] === 'boolean'
                    ? "row.getValue('{$column}') ? 'Sim' : 'Não'"
                    : "row.getValue('{$column}')",
            };

            $columns[] = <<<TSX
    {
        accessorKey: '{$column}',
        header: ({ column }) => (
            <Button
                variant="ghost"
                onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}
            >
                {$header}
                <ArrowUpDown className="ml-2 h-4 w-4" />
            </Button>
        ),
        cell: ({ row }) => {$cell},
    }
TSX;
        }

        // Adicionar colunas de contagem para HasMany
        foreach ($this->relationships['hasMany'] as $rel) {
            $header = Str::title(str_replace('_', ' ', Str::plural($rel['relatedModel'])));
            $method = $rel['method'];

            $columns[] = <<<TSX
    {
        accessorKey: '{$method}_count',
        header: '{$header}',
        cell: ({ row }) => (
            <span className="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">
                {row.original.{$method}_count ?? 0}
            </span>
        ),
    }
TSX;
        }

        return implode(",\n", $columns);
    }
}
