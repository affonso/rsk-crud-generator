<?php

declare(strict_types=1);

namespace Rsk\CrudGenerator\Contracts;

/**
 * Interface for mapping database types to input and TypeScript types.
 *
 * Provides type conversion utilities for generating forms,
 * validation rules, and TypeScript interfaces.
 */
interface TypeMapperInterface
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
    public function mapDatabaseType(string $dbType): array;

    /**
     * Get default validation rules for input type.
     *
     * Returns Laravel validation rules appropriate for the given input type.
     *
     * @param  string  $inputType  The HTML input type (e.g., 'text', 'number', 'email')
     * @return string Laravel validation rule string (e.g., 'string|max:255')
     */
    public function getValidationRules(string $inputType): string;

    /**
     * Get TypeScript type for database type.
     *
     * Converts a database column type to its TypeScript equivalent.
     *
     * @param  string  $dbType  The database column type
     * @return string The TypeScript type (e.g., 'string', 'number', 'boolean')
     */
    public function getTypeScriptType(string $dbType): string;
}
