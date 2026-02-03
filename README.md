# RSK CRUD Generator

![Laravel](https://img.shields.io/badge/Laravel-12%2B-red?style=flat-square&logo=laravel)
![Inertia.js](https://img.shields.io/badge/Inertia.js-v2-purple?style=flat-square)
![React](https://img.shields.io/badge/React-19-blue?style=flat-square&logo=react)
![Development Only](https://img.shields.io/badge/Environment-Development%20Only-orange?style=flat-square)

A powerful CRUD Generator for Laravel 12+ applications using Inertia.js v2, React 19, and shadcn/ui components. Generate complete, production-ready CRUD implementations from your existing Eloquent models with automatic relationship detection and type-safe TypeScript interfaces.

## Description

The RSK CRUD Generator automates the creation of complete CRUD (Create, Read, Update, Delete) implementations for your Laravel models. It provides both a visual wizard interface and a command-line interface to generate:

- Backend controllers with full CRUD operations
- Type-safe TypeScript interfaces
- React components with shadcn/ui design system
- Data tables with sorting, filtering, and pagination
- Form dialogs with validation
- Automatic relationship detection and handling

**Who is it for?**
- Laravel developers building admin panels or data management interfaces
- Teams using the Laravel + Inertia.js + React stack
- Developers who want to maintain consistency across CRUD implementations
- Projects requiring rapid prototyping with production-quality code

## Requirements

- **PHP**: 8.2 or higher
- **Laravel**: 12.0 or higher
- **Inertia.js**: v2
- **React**: 19
- **shadcn/ui**: Initialized in your project
- **Node.js/Bun**: For building frontend assets

## Installation

### Step 1: Install the package via Composer

Since this package is not yet published on Packagist, add the GitHub repository to your root `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/affonso/rsk-crud-generator"
        }
    ]
}
```

Then require the package:

```bash
composer require rsk/crud-generator:dev-main --dev
```

> **Note**: The `dev-main` specifies the main branch. Composer requires the `dev-` prefix for VCS repositories.

### Step 2: Run the installation command

```bash
php artisan crud-generator:install
```

This command will:
- Publish configuration files
- Publish frontend assets (wizard pages, types, components)
- Check prerequisites (Laravel version, Inertia.js, React structure)
- Verify required npm dependencies
- Verify required shadcn/ui components
- Create routes file if needed

### Step 3: Install missing dependencies (if any)

If the installation command reports missing dependencies, install them:

```bash
# Install npm dependencies (using bun)
bun add @tanstack/react-table @tanstack/react-virtual fuse.js usehooks-ts

# Or using npm
npm install @tanstack/react-table @tanstack/react-virtual fuse.js usehooks-ts

# Install missing shadcn/ui components
npx shadcn@latest add button card input select checkbox label table alert alert-dialog badge popover tooltip skeleton switch
```

### Step 4: Build frontend assets

```bash
# Using bun
bun run build
# or for development
bun run dev

# Or using npm
npm run build
# or for development
npm run dev
```

## Quick Start

### Generate your first CRUD in 3 steps:

#### Option 1: Using the Wizard (Recommended)

1. Start your development server:
   ```bash
   php artisan serve
   ```

2. Navigate to the CRUD Generator wizard:
   ```
   http://localhost:8000/admin/crud-generator
   ```

3. Follow the 3-step wizard:
   - **Step 1**: Select your model from the dropdown
   - **Step 2**: Configure field types, labels, and display options
   - **Step 3**: Review and generate files

#### Option 2: Using the Command Line

```bash
# Generate basic CRUD
php artisan make:shadcn-crud Post

# Generate with Form Requests for validation
php artisan make:shadcn-crud Post --with-requests

# Force overwrite existing files
php artisan make:shadcn-crud Post --force

# Use a custom configuration file
php artisan make:shadcn-crud Post --config-file=storage/app/crud-generator/Post.json
```

After generation, rebuild your frontend assets:

```bash
# Using bun
bun run build

# Or using npm
npm run build
```

## Usage

### Using the Wizard Interface

The wizard provides a visual interface for CRUD generation with three steps:

**Step 1: Model Selection**
- Browse all available Eloquent models in your application
- Preview model structure and relationships
- See detected table columns and fillable attributes

**Step 2: Field Configuration**
- Customize field labels and input types
- Toggle field visibility in table and form
- Configure display order
- Set validation rules
- Preview relationship fields automatically detected

**Step 3: Generation Result**
- Review files to be generated
- Choose whether to add routes automatically
- Choose whether to add navigation menu items
- Generate with one click

### Using the Artisan Command

The `make:shadcn-crud` command provides a command-line interface for generation:

```bash
php artisan make:shadcn-crud {model} [options]
```

#### Command Options

| Option | Description |
|--------|-------------|
| `--force` | Overwrite existing files without confirmation |
| `--with-requests` | Generate separate Form Request classes for validation |
| `--config-file=PATH` | Path to a JSON configuration file for field customization |
| `--api` | Generate API routes in addition to web routes (future feature) |

#### Examples

```bash
# Basic generation
php artisan make:shadcn-crud User

# With validation Form Requests
php artisan make:shadcn-crud Product --with-requests

# Using saved configuration from wizard
php artisan make:shadcn-crud Post --config-file=storage/app/crud-generator/Post.json

# Force regeneration (useful during development)
php artisan make:shadcn-crud Category --force --with-requests
```

## Configuration

After installation, the configuration file is published to `config/crud-generator.php`:

```php
return [
    // Where generated files should be placed
    'paths' => [
        'controllers' => 'app/Http/Controllers',
        'requests' => 'app/Http/Requests',
        'pages' => 'resources/js/pages',
        'types' => 'resources/js/types/models',
    ],

    // Namespace for your Eloquent models
    'models_namespace' => 'App\\Models',

    // Path to custom stubs (null = use package stubs)
    'stubs_path' => null,

    // Fields to try when displaying related models
    'display_field_candidates' => [
        'nome', 'name', 'title', 'titulo',
        'label', 'description', 'descricao', 'sigla'
    ],

    // Wizard route configuration
    'route_prefix' => 'admin/crud-generator',
    'middleware' => ['web', 'auth'],

    // Generated routes configuration
    'routes_file' => 'routes/rsk-crud.php',
    'routes_marker' => '// [RSK-CRUD-ROUTES]',

    // Navigation menu configuration
    'navigation_file' => 'rsk-crud-navigation.php',
    'navigation_marker' => '// [RSK-CRUD-NAV]',
];
```

### Key Configuration Options

**`paths`**: Customize where files are generated. Useful for non-standard project structures.

**`models_namespace`**: If your models are in a different namespace, update this.

**`stubs_path`**: Set to a custom path to use your own stub templates.

**`display_field_candidates`**: When generating relationship selects, the generator tries these fields in order to display related records.

**`routes_file`** and **`routes_marker`**: Control where generated routes are inserted.

**`navigation_file`** and **`navigation_marker`**: Control where navigation menu items are added.

## Customization

### Publishing Stubs

To customize the generated code templates, publish the stubs:

```bash
php artisan vendor:publish --tag=crud-generator-stubs
```

This will copy all stub files to `stubs/crud-generator/` in your project root.

### Modifying Stubs

After publishing, you can edit the stubs to match your preferences:

```
stubs/crud-generator/
├── controller.stub          # Backend controller template
├── form-request.stub        # Form Request validation template
├── types.stub               # TypeScript interface template
├── index-page.stub          # React index page with data table
├── form-dialog.stub         # React form dialog component
├── columns.stub             # Table column definitions
└── data-table.stub          # Data table component
```

Update your `config/crud-generator.php` to use the custom stubs:

```php
'stubs_path' => base_path('stubs/crud-generator'),
```

### Available Stub Placeholders

Stubs use double curly braces for placeholders:

**Model placeholders:**
- `{{ model }}` - Original model name
- `{{ modelStudly }}` - PascalCase (e.g., UserProfile)
- `{{ modelCamel }}` - camelCase (e.g., userProfile)
- `{{ modelPlural }}` - snake_case plural (e.g., user_profiles)
- `{{ modelPluralStudly }}` - PascalCase plural (e.g., UserProfiles)

**Field placeholders:**
- `{{ validationRules }}` - Validation rules array
- `{{ interfaceFields }}` - TypeScript interface properties
- `{{ tableColumns }}` - Column definitions for data table
- `{{ formFields }}` - Form input components

**Relationship placeholders:**
- `{{ relationshipImports }}` - Import statements for related models
- `{{ eagerLoadClause }}` - `->with()` calls for relationships
- `{{ withCountClause }}` - `->withCount()` calls for counts
- `{{ relationshipProps }}` - Props for relationship data

## Generated Files

For a model named `Post`, the generator creates:

```
app/
└── Http/
    ├── Controllers/
    │   └── PostController.php
    └── Requests/
        └── Post/
            ├── StorePostRequest.php (optional, with --with-requests)
            └── UpdatePostRequest.php (optional, with --with-requests)

resources/
└── js/
    ├── pages/
    │   └── Posts/
    │       ├── Index.tsx
    │       ├── FormDialog.tsx
    │       ├── columns.tsx
    │       └── DataTable.tsx
    └── types/
        └── models/
            └── post.ts
```

### File Descriptions

**PostController.php**: RESTful controller with:
- `index()` - List with pagination, eager loading, and counts
- `store()` - Create new record with validation
- `update()` - Update existing record with validation
- `destroy()` - Delete record

**StorePostRequest.php / UpdatePostRequest.php**: Form Request classes with validation rules and custom error messages.

**Index.tsx**: Main page component with data table, search, and filters.

**FormDialog.tsx**: Modal dialog with form for creating/editing records.

**columns.tsx**: Column definitions for the data table with sorting and formatting.

**DataTable.tsx**: Reusable data table component with built-in features.

**post.ts**: TypeScript interface matching your model structure.

## Prerequisites

### Required npm Packages

The following npm packages must be installed:

```bash
# Using bun
bun add @tanstack/react-table @tanstack/react-virtual fuse.js usehooks-ts

# Or using npm
npm install @tanstack/react-table @tanstack/react-virtual fuse.js usehooks-ts
```

- **@tanstack/react-table**: Powerful table library for building data tables
- **@tanstack/react-virtual**: Virtual scrolling for large data sets
- **fuse.js**: Fuzzy search functionality
- **usehooks-ts**: Utility React hooks

### Required shadcn/ui Components

The following shadcn/ui components must be installed:

```bash
npx shadcn@latest add button card input select checkbox label table alert alert-dialog badge popover tooltip skeleton switch
```

- **button**: Action buttons and form submissions
- **card**: Container for content sections
- **input**: Text input fields
- **select**: Dropdown selections
- **checkbox**: Boolean fields
- **label**: Form field labels
- **table**: Data table display
- **alert**: Notifications and messages
- **alert-dialog**: Confirmation dialogs (delete operations)
- **badge**: Status indicators
- **popover**: Context menus and tooltips
- **tooltip**: Hover information
- **skeleton**: Loading states
- **switch**: Toggle switches for boolean settings

The installation command automatically checks for these prerequisites and provides installation instructions if any are missing.

## Architecture

The CRUD Generator is built with a clean, service-oriented architecture:

### Core Services

**ModelIntrospector** (`Services/ModelIntrospector.php`)
- Analyzes Eloquent models using PHP Reflection
- Extracts table structure, columns, and fillable attributes
- Detects column types and constraints

**RelationshipDetector** (`Services/RelationshipDetector.php`)
- Identifies model relationships (BelongsTo, HasMany, BelongsToMany)
- Determines relationship types and related models
- Finds appropriate display fields for related records

**TypeMapper** (`Services/TypeMapper.php`)
- Maps database column types to input types
- Converts PHP types to TypeScript types
- Provides default values based on types

**ConfigurationManager** (`Services/ConfigurationManager.php`)
- Manages custom field configurations
- Handles JSON config file loading and saving
- Merges custom config with detected structure

### Controllers

**CrudGeneratorController** (`Http/Controllers/CrudGeneratorController.php`)
- Powers the wizard interface
- Provides API endpoints for model introspection
- Handles configuration storage and retrieval

### Commands

**MakeShadcnCrud** (`Console/Commands/MakeShadcnCrud.php`)
- Main generation command
- Orchestrates the generation process
- Manages file creation and stub replacement

**InstallCrudGenerator** (`Console/Commands/InstallCrudGenerator.php`)
- Handles package installation
- Checks prerequisites
- Publishes assets and configuration

### Frontend Components

**Wizard Pages** (`resources/js/pages/CrudGenerator/`)
- Three-step wizard interface
- Model selection, field configuration, and result preview
- Real-time validation and feedback

**CRUD Manager** (`resources/js/pages/CrudManager/`)
- Management interface for generated CRUDs
- View, edit, and delete generated implementations

## Contributing

Contributions are welcome! This package is part of the Laravel React Starter Kit ecosystem.

### Development Guidelines

1. Follow PSR-12 coding standards for PHP
2. Use Laravel Pint for code formatting: `composer pint` or `vendor/bin/pint`
3. Write tests for new features using Pest
4. Update documentation for user-facing changes
5. Follow existing naming conventions in stubs

### Development Commands

**Code formatting:**
```bash
# Format code with Laravel Pint
composer pint
# or
vendor/bin/pint
```

**Dependency management:**
```bash
# Install dependencies
composer install

# Update dependencies
composer update

# Require new package (dev only)
composer require package/name --dev
```

### Testing

Run the test suite:

```bash
composer test
```

Run specific tests:

```bash
composer test -- --filter=ModelIntrospectorTest
```

Run tests with coverage:

```bash
composer test -- --coverage
```

### Reporting Issues

When reporting issues, please include:
- Laravel version
- PHP version
- Steps to reproduce
- Expected vs actual behavior
- Sample model structure (if relevant)

## License

The RSK CRUD Generator is open-sourced software licensed under the [MIT license](LICENSE).

---

**Part of the Laravel React Starter Kit** - Building modern, type-safe Laravel applications with React and Inertia.js.
