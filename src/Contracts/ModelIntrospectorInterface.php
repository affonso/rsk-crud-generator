<?php

declare(strict_types=1);

namespace Rsk\CrudGenerator\Contracts;

/**
 * Interface for model introspection operations.
 *
 * Responsible for discovering available Eloquent models,
 * extracting schema information, and building field configurations.
 */
interface ModelIntrospectorInterface
{
    /**
     * Get all available Eloquent models in app/Models.
     *
     * Scans the models directory and returns an array of model information
     * including the model name and fully qualified class name.
     *
     * @return array<int, array{name: string, class: string}>
     */
    public function getAvailableModels(): array;

    /**
     * Get model table name, columns, and fillable fields.
     *
     * Inspects the given model class to extract its database table name,
     * all column names, and the list of fillable attributes.
     *
     * @param  string  $modelClass  Fully qualified model class name
     * @return array{table: string, columns: array<string>, fillable: array<string>}
     *
     * @throws \InvalidArgumentException When the model class does not exist
     */
    public function getModelSchema(string $modelClass): array;

    /**
     * Build field configurations from fillable fields.
     *
     * Creates an array of field configurations suitable for form generation,
     * including field names, labels, types, and validation rules.
     *
     * @param  string  $table  The database table name
     * @param  array<string>  $fillable  List of fillable field names
     * @param  array<string, array{relatedModel: string, foreignKey: string, displayField: string}>  $relationshipMap  Foreign key => relationship data
     * @return array<int, array{
     *     name: string,
     *     label: string,
     *     type: string,
     *     inputType: string,
     *     required: bool,
     *     validation: string,
     *     relationship?: array{relatedModel: string, foreignKey: string, displayField: string}
     * }>
     */
    public function buildFieldConfigurations(
        string $table,
        array $fillable,
        array $relationshipMap,
        TypeMapperInterface $typeMapper
    ): array;

    /**
     * Guess the best display field for a table.
     *
     * Attempts to find a suitable field to display as the label
     * for records (e.g., 'name', 'title', 'label').
     *
     * @param  string  $table  The database table name
     * @return string The best display field name, or 'id' as fallback
     */
    public function guessDisplayField(string $table): string;
}
