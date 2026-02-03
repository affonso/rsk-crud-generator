<?php

declare(strict_types=1);

namespace Rsk\CrudGenerator\Contracts;

/**
 * Interface for managing CRUD configuration files.
 *
 * Handles adding routes and navigation items for generated CRUD resources.
 */
interface ConfigurationManagerInterface
{
    /**
     * Add resource routes to the routes file.
     *
     * Inserts the necessary route declarations for the CRUD resource
     * into the designated routes file at the appropriate marker location.
     *
     * @param  string  $model  The model name (e.g., 'User', 'Post')
     * @return array{added: bool, message: string} Result with success status and message
     */
    public function addCrudRoutes(string $model): array;

    /**
     * Add navigation item to the navigation config.
     *
     * Inserts a new navigation entry for the CRUD resource
     * into the navigation configuration file.
     *
     * @param  string  $model  The model name (e.g., 'User', 'Post')
     * @param  string  $icon  The icon identifier for the navigation item (e.g., 'users', 'file-text')
     * @return array{added: bool, message: string} Result with success status and message
     */
    public function addNavigationItem(string $model, string $icon): array;
}
