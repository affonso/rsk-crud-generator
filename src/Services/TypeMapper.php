<?php

declare(strict_types=1);

namespace Rsk\CrudGenerator\Services;

use Rsk\CrudGenerator\Contracts\TypeMapperInterface;

/**
 * Maps database column types to input types, validation rules, and TypeScript types.
 *
 * This service provides type conversion utilities for generating forms,
 * validation rules, and TypeScript interfaces from Laravel models.
 */
class TypeMapper implements TypeMapperInterface
{
    /**
     * Map database type to input type and TypeScript type.
     *
     * Converts a database column type (e.g., 'varchar', 'integer', 'boolean')
     * to the appropriate HTML input type and TypeScript type.
     *
     * @param  string  $dbType  The database column type
     * @return array{type: string, input: string} Contains 'type' (TypeScript) and 'input' (HTML input type)
     */
    public function mapDatabaseType(string $dbType): array
    {
        return match ($dbType) {
            'integer', 'bigint', 'smallint' => ['type' => 'number', 'input' => 'number'],
            'decimal', 'float', 'double' => ['type' => 'number', 'input' => 'number'],
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
     * Get default validation rules for input type.
     *
     * Returns Laravel validation rules appropriate for the given input type.
     *
     * @param  string  $inputType  The HTML input type (e.g., 'text', 'number', 'email')
     * @return string Laravel validation rule string (e.g., 'string|max:255')
     */
    public function getValidationRules(string $inputType): string
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

    /**
     * Get TypeScript type for database type.
     *
     * Converts a database column type to its TypeScript equivalent.
     *
     * @param  string  $dbType  The database column type
     * @return string The TypeScript type (e.g., 'string', 'number', 'boolean')
     */
    public function getTypeScriptType(string $dbType): string
    {
        $mapping = $this->mapDatabaseType($dbType);

        return $mapping['type'];
    }
}
