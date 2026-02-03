<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Output Paths
    |--------------------------------------------------------------------------
    |
    | Define where the generated files should be placed. These paths are
    | relative to the Laravel application root.
    |
    */

    'paths' => [
        'controllers' => 'app/Http/Controllers',
        'requests' => 'app/Http/Requests',
        'pages' => 'resources/js/pages',
        'types' => 'resources/js/types/models',
    ],

    /*
    |--------------------------------------------------------------------------
    | Models Namespace
    |--------------------------------------------------------------------------
    |
    | The namespace where your Eloquent models are located.
    |
    */

    'models_namespace' => 'App\\Models',

    /*
    |--------------------------------------------------------------------------
    | Custom Stubs Path
    |--------------------------------------------------------------------------
    |
    | If you want to use custom stubs, set this to the path of your stubs
    | directory. Set to null to use the default package stubs.
    |
    */

    'stubs_path' => null,

    /*
    |--------------------------------------------------------------------------
    | Display Field Candidates
    |--------------------------------------------------------------------------
    |
    | When detecting relationships, the generator tries to find the best
    | field to display for related models. These are checked in order.
    |
    */

    'display_field_candidates' => [
        'nome',
        'name',
        'title',
        'titulo',
        'label',
        'description',
        'descricao',
        'sigla',
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    |
    | The prefix for the CRUD Generator wizard routes.
    |
    */

    'route_prefix' => 'admin/crud-generator',

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware to apply to the CRUD Generator wizard routes.
    |
    */

    'middleware' => ['web', 'auth'],

    /*
    |--------------------------------------------------------------------------
    | Routes File
    |--------------------------------------------------------------------------
    |
    | The file where generated CRUD routes should be added.
    |
    */

    'routes_file' => 'routes/rsk-crud.php',

    /*
    |--------------------------------------------------------------------------
    | Routes Marker
    |--------------------------------------------------------------------------
    |
    | The marker comment in the routes file where new routes will be inserted.
    |
    */

    'routes_marker' => '// [RSK-CRUD-ROUTES]',

    /*
    |--------------------------------------------------------------------------
    | Navigation File
    |--------------------------------------------------------------------------
    |
    | The config file where navigation items should be added.
    |
    */

    'navigation_file' => 'config/rsk-crud-navigation.php',

    /*
    |--------------------------------------------------------------------------
    | Navigation Marker
    |--------------------------------------------------------------------------
    |
    | The marker comment in the navigation file where new items will be inserted.
    |
    */

    'navigation_marker' => '// [RSK-CRUD-NAV]',

];
