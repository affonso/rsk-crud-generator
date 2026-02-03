<?php

declare(strict_types=1);

namespace Rsk\CrudGenerator\Contracts;

/**
 * Interface for detecting Eloquent model relationships.
 *
 * Uses PHP Reflection to analyze model methods and detect
 * BelongsTo, HasMany, and BelongsToMany relationships.
 */
interface RelationshipDetectorInterface
{
    /**
     * Detect all relationships using Reflection.
     *
     * Analyzes the model class methods to find relationship definitions
     * and returns categorized arrays for each relationship type.
     *
     * @param  object  $instance  An instance of the model
     * @param  string  $modelClass  Fully qualified model class name
     * @return array{
     *     belongsTo: array<int, array{
     *         method: string,
     *         relatedModel: string,
     *         foreignKey: string,
     *         displayField: string
     *     }>,
     *     hasMany: array<int, array{
     *         method: string,
     *         relatedModel: string,
     *         foreignKey: string
     *     }>,
     *     belongsToMany: array<int, array{
     *         method: string,
     *         relatedModel: string,
     *         pivotTable: string
     *     }>
     * }
     */
    public function detectRelationships(object $instance, string $modelClass): array;

    /**
     * Create map of foreign keys to relationship metadata.
     *
     * Transforms the belongsTo relationships array into a map
     * keyed by foreign key names for easy lookup during field configuration.
     *
     * @param  array{belongsTo: array<int, array{
     *     method: string,
     *     relatedModel: string,
     *     foreignKey: string,
     *     displayField: string
     * }>}  $relationships  The detected relationships array
     * @return array<string, array{
     *     relatedModel: string,
     *     foreignKey: string,
     *     displayField: string,
     *     method: string
     * }>
     */
    public function mapForeignKeysToRelationships(array $relationships): array;
}
