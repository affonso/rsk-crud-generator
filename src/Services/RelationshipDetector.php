<?php

namespace Rsk\CrudGenerator\Services;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use ReflectionClass;
use ReflectionMethod;
use Rsk\CrudGenerator\Contracts\ModelIntrospectorInterface;
use Rsk\CrudGenerator\Contracts\RelationshipDetectorInterface;

class RelationshipDetector implements RelationshipDetectorInterface
{
    public function __construct(
        private ModelIntrospectorInterface $modelIntrospector
    ) {}

    /**
     * Detecta relacionamentos definidos no Model usando Reflection.
     */
    public function detectRelationships(object $instance, string $modelClass): array
    {
        $relationships = [
            'belongsTo' => [],
            'hasMany' => [],
            'belongsToMany' => [],
        ];

        $reflection = new ReflectionClass($instance);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            // Ignorar métodos herdados e com parâmetros obrigatórios
            if ($method->class !== $modelClass) {
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
                        'displayField' => $this->modelIntrospector->guessDisplayField($relatedTable),
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
     * Cria um mapa de foreign keys para relacionamentos BelongsTo.
     */
    public function mapForeignKeysToRelationships(array $relationships): array
    {
        $fkToRelationship = [];

        foreach ($relationships['belongsTo'] as $rel) {
            $fkToRelationship[$rel['foreignKey']] = $rel;
        }

        return $fkToRelationship;
    }
}
