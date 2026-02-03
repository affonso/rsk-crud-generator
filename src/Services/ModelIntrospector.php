<?php

declare(strict_types=1);

namespace Rsk\CrudGenerator\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Rsk\CrudGenerator\Contracts\ModelIntrospectorInterface;
use Rsk\CrudGenerator\Contracts\TypeMapperInterface;

/**
 * Service for introspecting Eloquent models and extracting schema information.
 *
 * Handles model discovery, schema extraction, and field configuration building
 * for CRUD generation.
 */
class ModelIntrospector implements ModelIntrospectorInterface
{
    public function __construct(
        private TypeMapperInterface $typeMapper
    ) {}

    /**
     * Get all available Eloquent models in app/Models.
     *
     * @return array<int, array{name: string, class: string}>
     */
    public function getAvailableModels(): array
    {
        $modelsNamespace = config('crud-generator.models_namespace', 'App\\Models');
        $modelsPath = app_path('Models');
        $models = [];

        if (! File::isDirectory($modelsPath)) {
            return $models;
        }

        $files = File::files($modelsPath);

        foreach ($files as $file) {
            $className = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            $fullClass = $modelsNamespace.'\\'.$className;

            if (class_exists($fullClass) && is_subclass_of($fullClass, Model::class)) {
                $models[] = [
                    'name' => $className,
                    'class' => $fullClass,
                ];
            }
        }

        return $models;
    }

    /**
     * Get model table name, columns, and fillable fields.
     *
     * @param  string  $modelClass  Fully qualified model class name
     * @return array{table: string, columns: array<string>, fillable: array<string>}
     *
     * @throws \InvalidArgumentException When the model class does not exist
     */
    public function getModelSchema(string $modelClass): array
    {
        if (! class_exists($modelClass)) {
            throw new InvalidArgumentException("Model class {$modelClass} does not exist.");
        }

        $instance = new $modelClass;
        $table = $instance->getTable();
        $fillable = $instance->getFillable();
        $columns = Schema::getColumnListing($table);

        // If no fillable defined, use all columns except common timestamps
        if (empty($fillable)) {
            $fillable = array_values(array_diff($columns, ['id', 'created_at', 'updated_at', 'deleted_at']));
        }

        return [
            'table' => $table,
            'columns' => $columns,
            'fillable' => $fillable,
        ];
    }

    /**
     * Build field configurations from fillable fields.
     *
     * @param  string  $table  The database table name
     * @param  array<string>  $fillable  List of fillable field names
     * @param  array<string, array{method: string, relatedModel: string, foreignKey: string, displayField: string, relatedTable: string}>  $relationshipMap  Foreign key => relationship data
     * @param  TypeMapperInterface  $typeMapper  Type mapper instance
     * @return array<int, array{
     *     name: string,
     *     label: string,
     *     dbType: string,
     *     inputType: string,
     *     tsType: string,
     *     required: bool,
     *     validation: string,
     *     isRelationship: bool,
     *     relationshipType?: string,
     *     relationshipMethod?: string,
     *     relatedModel?: string,
     *     relatedTable?: string,
     *     displayField?: string,
     *     relatedColumns?: array<string>
     * }>
     */
    public function buildFieldConfigurations(
        string $table,
        array $fillable,
        array $relationshipMap,
        TypeMapperInterface $typeMapper
    ): array {
        $fields = [];

        foreach ($fillable as $column) {
            $dbType = Schema::getColumnType($table, $column);
            $fieldConfig = $typeMapper->mapDatabaseType($dbType);

            $field = [
                'name' => $column,
                'label' => Str::title(str_replace('_', ' ', $column)),
                'dbType' => $dbType,
                'inputType' => $fieldConfig['input'],
                'tsType' => $fieldConfig['type'],
                'required' => true,
                'validation' => $typeMapper->getValidationRules($fieldConfig['input']),
                'isRelationship' => false,
            ];

            // Check if this column is a foreign key in a relationship
            if (isset($relationshipMap[$column])) {
                $rel = $relationshipMap[$column];
                $field['isRelationship'] = true;
                $field['relationshipType'] = 'belongsTo';
                $field['relationshipMethod'] = $rel['method'];
                $field['relatedModel'] = $rel['relatedModel'];
                $field['relatedTable'] = $rel['relatedTable'];
                $field['displayField'] = $rel['displayField'];
                $field['relatedColumns'] = Schema::getColumnListing($rel['relatedTable']);
                $field['inputType'] = 'relationship-select';
                $field['label'] = Str::title(str_replace('_', ' ', $rel['method']));
                $field['validation'] = 'required|exists:'.$rel['relatedTable'].',id';
            }

            $fields[] = $field;
        }

        return $fields;
    }

    /**
     * Guess the best display field for a table.
     *
     * @param  string  $table  The database table name
     * @return string The best display field name, or 'id' as fallback
     */
    public function guessDisplayField(string $table): string
    {
        $commonNames = config(
            'crud-generator.display_field_candidates',
            ['nome', 'name', 'title', 'titulo', 'label', 'description', 'descricao', 'sigla']
        );

        $columns = Schema::getColumnListing($table);

        foreach ($commonNames as $name) {
            if (in_array($name, $columns)) {
                return $name;
            }
        }

        return 'id';
    }
}
