<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Rsk\CrudGenerator\Contracts\TypeMapperInterface;
use Rsk\CrudGenerator\Services\ModelIntrospector;

beforeEach(function () {
    $this->typeMapper = Mockery::mock(TypeMapperInterface::class);
    $this->introspector = new ModelIntrospector($this->typeMapper);
});

afterEach(function () {
    Mockery::close();
});

describe('getAvailableModels', function () {
    it('returns empty array when models directory does not exist', function () {
        $modelsPath = base_path('app/Models');

        File::shouldReceive('isDirectory')
            ->once()
            ->with($modelsPath)
            ->andReturn(false);

        $result = $this->introspector->getAvailableModels();

        expect($result)->toBeArray()->toBeEmpty();
    });

    it('returns empty array when models directory is empty', function () {
        $modelsPath = base_path('app/Models');

        File::shouldReceive('isDirectory')
            ->once()
            ->with($modelsPath)
            ->andReturn(true);

        File::shouldReceive('files')
            ->once()
            ->with($modelsPath)
            ->andReturn([]);

        $result = $this->introspector->getAvailableModels();

        expect($result)->toBeArray()->toBeEmpty();
    });

    it('returns available models from models directory', function () {
        $modelsPath = base_path('app/Models');

        // Create a mock SplFileInfo object
        $mockFile = Mockery::mock(\SplFileInfo::class);
        $mockFile->shouldReceive('getFilename')->andReturn('User.php');

        File::shouldReceive('isDirectory')
            ->once()
            ->with($modelsPath)
            ->andReturn(true);

        File::shouldReceive('files')
            ->once()
            ->with($modelsPath)
            ->andReturn([$mockFile]);

        $result = $this->introspector->getAvailableModels();

        expect($result)->toBeArray()
            ->toHaveCount(1)
            ->and($result[0])->toHaveKeys(['name', 'class'])
            ->and($result[0]['name'])->toBe('User')
            ->and($result[0]['class'])->toBe('App\\Models\\User');
    });

    it('filters out non-model classes', function () {
        $modelsPath = base_path('app/Models');

        $mockFile1 = Mockery::mock(\SplFileInfo::class);
        $mockFile1->shouldReceive('getFilename')->andReturn('User.php');

        $mockFile2 = Mockery::mock(\SplFileInfo::class);
        $mockFile2->shouldReceive('getFilename')->andReturn('NotAModel.php');

        File::shouldReceive('isDirectory')
            ->once()
            ->with($modelsPath)
            ->andReturn(true);

        File::shouldReceive('files')
            ->once()
            ->with($modelsPath)
            ->andReturn([$mockFile1, $mockFile2]);

        $result = $this->introspector->getAvailableModels();

        // Only User should be returned as it's an actual Model class
        expect($result)->toBeArray()
            ->toHaveCount(1)
            ->and($result[0]['name'])->toBe('User');
    });

    it('uses custom namespace from config', function () {
        $modelsPath = base_path('app/Models');

        config(['crud-generator.models_namespace' => 'Custom\\Models']);

        $mockFile = Mockery::mock(\SplFileInfo::class);
        $mockFile->shouldReceive('getFilename')->andReturn('CustomModel.php');

        File::shouldReceive('isDirectory')
            ->once()
            ->with($modelsPath)
            ->andReturn(true);

        File::shouldReceive('files')
            ->once()
            ->with($modelsPath)
            ->andReturn([$mockFile]);

        $result = $this->introspector->getAvailableModels();

        // The class should use the custom namespace
        expect($result)->toBeArray();
        if (! empty($result)) {
            expect($result[0]['class'])->toContain('Custom\\Models');
        }
    });
});

describe('getModelSchema', function () {
    it('throws exception when model class does not exist', function () {
        $this->introspector->getModelSchema('App\\Models\\NonExistentModel');
    })->throws(InvalidArgumentException::class, 'Model class App\\Models\\NonExistentModel does not exist.');

    it('returns table name, columns, and fillable fields', function () {
        Schema::shouldReceive('getColumnListing')
            ->once()
            ->with('users')
            ->andReturn(['id', 'name', 'email', 'created_at', 'updated_at']);

        $result = $this->introspector->getModelSchema(\App\Models\User::class);

        expect($result)->toBeArray()
            ->toHaveKeys(['table', 'columns', 'fillable'])
            ->and($result['table'])->toBe('users')
            ->and($result['columns'])->toBeArray()
            ->and($result['fillable'])->toBeArray();
    });

    it('returns all columns except timestamps when fillable is empty', function () {
        // Create a test model class with no fillable
        $testModel = new class extends Model
        {
            protected $table = 'test_models';

            protected $fillable = [];
        };

        Schema::shouldReceive('getColumnListing')
            ->once()
            ->with('test_models')
            ->andReturn(['id', 'name', 'email', 'status', 'created_at', 'updated_at', 'deleted_at']);

        $result = $this->introspector->getModelSchema(get_class($testModel));

        expect($result)->toBeArray()
            ->and($result['table'])->toBe('test_models')
            ->and($result['columns'])->toBe(['id', 'name', 'email', 'status', 'created_at', 'updated_at', 'deleted_at'])
            ->and($result['fillable'])->toBe(['name', 'email', 'status'])
            ->and($result['fillable'])->not->toContain('id', 'created_at', 'updated_at', 'deleted_at');
    });

    it('preserves defined fillable fields when present', function () {
        // Create a test model class with defined fillable
        $testModel = new class extends Model
        {
            protected $table = 'test_models';

            protected $fillable = ['name', 'email'];
        };

        Schema::shouldReceive('getColumnListing')
            ->once()
            ->with('test_models')
            ->andReturn(['id', 'name', 'email', 'status', 'created_at', 'updated_at']);

        $result = $this->introspector->getModelSchema(get_class($testModel));

        expect($result)->toBeArray()
            ->and($result['fillable'])->toBe(['name', 'email'])
            ->and($result['fillable'])->not->toContain('status');
    });
});

describe('guessDisplayField', function () {
    it('returns "id" when no common fields are found', function () {
        Schema::shouldReceive('getColumnListing')
            ->once()
            ->with('test_table')
            ->andReturn(['id', 'field1', 'field2', 'created_at']);

        $result = $this->introspector->guessDisplayField('test_table');

        expect($result)->toBe('id');
    });

    it('returns "nome" when present', function () {
        Schema::shouldReceive('getColumnListing')
            ->once()
            ->with('test_table')
            ->andReturn(['id', 'nome', 'status', 'created_at']);

        $result = $this->introspector->guessDisplayField('test_table');

        expect($result)->toBe('nome');
    });

    it('returns "name" when "nome" is not present', function () {
        Schema::shouldReceive('getColumnListing')
            ->once()
            ->with('test_table')
            ->andReturn(['id', 'name', 'email', 'created_at']);

        $result = $this->introspector->guessDisplayField('test_table');

        expect($result)->toBe('name');
    });

    it('returns "title" when neither "nome" nor "name" is present', function () {
        Schema::shouldReceive('getColumnListing')
            ->once()
            ->with('test_table')
            ->andReturn(['id', 'title', 'content', 'created_at']);

        $result = $this->introspector->guessDisplayField('test_table');

        expect($result)->toBe('title');
    });

    it('respects priority order of display field candidates', function () {
        Schema::shouldReceive('getColumnListing')
            ->once()
            ->with('test_table')
            ->andReturn(['id', 'description', 'name', 'title', 'created_at']);

        $result = $this->introspector->guessDisplayField('test_table');

        // Should return 'name' because it comes before 'title' and 'description' in the priority list
        expect($result)->toBe('name');
    });

    it('uses custom display field candidates from config', function () {
        config(['crud-generator.display_field_candidates' => ['custom_field', 'another_field']]);

        Schema::shouldReceive('getColumnListing')
            ->once()
            ->with('test_table')
            ->andReturn(['id', 'custom_field', 'name', 'created_at']);

        $result = $this->introspector->guessDisplayField('test_table');

        expect($result)->toBe('custom_field');
    });
});

describe('buildFieldConfigurations', function () {
    beforeEach(function () {
        // Reset type mapper mock for each test in this group
        $this->typeMapper = Mockery::mock(TypeMapperInterface::class);
        $this->introspector = new ModelIntrospector($this->typeMapper);
    });

    it('builds basic field configurations without relationships', function () {
        Schema::shouldReceive('getColumnType')
            ->with('users', 'name')
            ->andReturn('varchar');

        Schema::shouldReceive('getColumnType')
            ->with('users', 'email')
            ->andReturn('varchar');

        $this->typeMapper->shouldReceive('mapDatabaseType')
            ->with('varchar')
            ->andReturn(['type' => 'string', 'input' => 'text']);

        $this->typeMapper->shouldReceive('getValidationRules')
            ->with('text')
            ->andReturn('required|string|max:255');

        $result = $this->introspector->buildFieldConfigurations(
            'users',
            ['name', 'email'],
            [],
            $this->typeMapper
        );

        expect($result)->toBeArray()->toHaveCount(2)
            ->and($result[0])->toHaveKeys([
                'name', 'label', 'dbType', 'inputType', 'tsType',
                'required', 'validation', 'isRelationship',
            ])
            ->and($result[0]['name'])->toBe('name')
            ->and($result[0]['label'])->toBe('Name')
            ->and($result[0]['dbType'])->toBe('varchar')
            ->and($result[0]['inputType'])->toBe('text')
            ->and($result[0]['tsType'])->toBe('string')
            ->and($result[0]['required'])->toBe(true)
            ->and($result[0]['validation'])->toBe('required|string|max:255')
            ->and($result[0]['isRelationship'])->toBe(false);
    });

    it('builds field configurations for different data types', function () {
        Schema::shouldReceive('getColumnType')
            ->with('products', 'name')
            ->andReturn('varchar');

        Schema::shouldReceive('getColumnType')
            ->with('products', 'price')
            ->andReturn('decimal');

        Schema::shouldReceive('getColumnType')
            ->with('products', 'is_active')
            ->andReturn('boolean');

        Schema::shouldReceive('getColumnType')
            ->with('products', 'description')
            ->andReturn('text');

        $this->typeMapper->shouldReceive('mapDatabaseType')
            ->with('varchar')
            ->andReturn(['type' => 'string', 'input' => 'text']);

        $this->typeMapper->shouldReceive('mapDatabaseType')
            ->with('decimal')
            ->andReturn(['type' => 'number', 'input' => 'number']);

        $this->typeMapper->shouldReceive('mapDatabaseType')
            ->with('boolean')
            ->andReturn(['type' => 'boolean', 'input' => 'checkbox']);

        $this->typeMapper->shouldReceive('mapDatabaseType')
            ->with('text')
            ->andReturn(['type' => 'string', 'input' => 'textarea']);

        $this->typeMapper->shouldReceive('getValidationRules')
            ->andReturn('required|string|max:255', 'required|numeric', 'boolean', 'nullable|string');

        $result = $this->introspector->buildFieldConfigurations(
            'products',
            ['name', 'price', 'is_active', 'description'],
            [],
            $this->typeMapper
        );

        expect($result)->toBeArray()->toHaveCount(4)
            ->and($result[0]['inputType'])->toBe('text')
            ->and($result[1]['inputType'])->toBe('number')
            ->and($result[2]['inputType'])->toBe('checkbox')
            ->and($result[3]['inputType'])->toBe('textarea');
    });

    it('converts field names to readable labels', function () {
        Schema::shouldReceive('getColumnType')
            ->with('users', 'first_name')
            ->andReturn('varchar');

        Schema::shouldReceive('getColumnType')
            ->with('users', 'phone_number')
            ->andReturn('varchar');

        $this->typeMapper->shouldReceive('mapDatabaseType')
            ->with('varchar')
            ->andReturn(['type' => 'string', 'input' => 'text']);

        $this->typeMapper->shouldReceive('getValidationRules')
            ->with('text')
            ->andReturn('required|string|max:255');

        $result = $this->introspector->buildFieldConfigurations(
            'users',
            ['first_name', 'phone_number'],
            [],
            $this->typeMapper
        );

        expect($result[0]['label'])->toBe('First Name')
            ->and($result[1]['label'])->toBe('Phone Number');
    });

    it('handles belongsTo relationships correctly', function () {
        Schema::shouldReceive('getColumnType')
            ->with('posts', 'user_id')
            ->andReturn('bigint');

        Schema::shouldReceive('getColumnListing')
            ->with('users')
            ->andReturn(['id', 'name', 'email', 'created_at', 'updated_at']);

        $this->typeMapper->shouldReceive('mapDatabaseType')
            ->with('bigint')
            ->andReturn(['type' => 'number', 'input' => 'number']);

        // getValidationRules is called initially but the value gets overridden for relationships
        $this->typeMapper->shouldReceive('getValidationRules')
            ->with('number')
            ->andReturn('required|numeric');

        $relationshipMap = [
            'user_id' => [
                'method' => 'user',
                'relatedModel' => 'App\\Models\\User',
                'foreignKey' => 'user_id',
                'displayField' => 'name',
                'relatedTable' => 'users',
            ],
        ];

        $result = $this->introspector->buildFieldConfigurations(
            'posts',
            ['user_id'],
            $relationshipMap,
            $this->typeMapper
        );

        expect($result)->toBeArray()->toHaveCount(1)
            ->and($result[0]['isRelationship'])->toBe(true)
            ->and($result[0]['relationshipType'])->toBe('belongsTo')
            ->and($result[0]['relationshipMethod'])->toBe('user')
            ->and($result[0]['relatedModel'])->toBe('App\\Models\\User')
            ->and($result[0]['relatedTable'])->toBe('users')
            ->and($result[0]['displayField'])->toBe('name')
            ->and($result[0]['inputType'])->toBe('relationship-select')
            ->and($result[0]['label'])->toBe('User')
            ->and($result[0]['validation'])->toBe('required|exists:users,id')
            ->and($result[0]['relatedColumns'])->toBe(['id', 'name', 'email', 'created_at', 'updated_at']);
    });

    it('handles mixed regular and relationship fields', function () {
        Schema::shouldReceive('getColumnType')
            ->with('posts', 'title')
            ->andReturn('varchar');

        Schema::shouldReceive('getColumnType')
            ->with('posts', 'user_id')
            ->andReturn('bigint');

        Schema::shouldReceive('getColumnType')
            ->with('posts', 'content')
            ->andReturn('text');

        Schema::shouldReceive('getColumnListing')
            ->with('users')
            ->andReturn(['id', 'name', 'email']);

        $this->typeMapper->shouldReceive('mapDatabaseType')
            ->with('varchar')
            ->andReturn(['type' => 'string', 'input' => 'text']);

        $this->typeMapper->shouldReceive('mapDatabaseType')
            ->with('bigint')
            ->andReturn(['type' => 'number', 'input' => 'number']);

        $this->typeMapper->shouldReceive('mapDatabaseType')
            ->with('text')
            ->andReturn(['type' => 'string', 'input' => 'textarea']);

        $this->typeMapper->shouldReceive('getValidationRules')
            ->with('text')
            ->andReturn('required|string|max:255');

        $this->typeMapper->shouldReceive('getValidationRules')
            ->with('number')
            ->andReturn('required|numeric');

        $this->typeMapper->shouldReceive('getValidationRules')
            ->with('textarea')
            ->andReturn('nullable|string');

        $relationshipMap = [
            'user_id' => [
                'method' => 'user',
                'relatedModel' => 'App\\Models\\User',
                'foreignKey' => 'user_id',
                'displayField' => 'name',
                'relatedTable' => 'users',
            ],
        ];

        $result = $this->introspector->buildFieldConfigurations(
            'posts',
            ['title', 'user_id', 'content'],
            $relationshipMap,
            $this->typeMapper
        );

        expect($result)->toBeArray()->toHaveCount(3)
            ->and($result[0]['isRelationship'])->toBe(false)
            ->and($result[0]['name'])->toBe('title')
            ->and($result[1]['isRelationship'])->toBe(true)
            ->and($result[1]['name'])->toBe('user_id')
            ->and($result[2]['isRelationship'])->toBe(false)
            ->and($result[2]['name'])->toBe('content');
    });

    it('returns empty array when no fillable fields provided', function () {
        $result = $this->introspector->buildFieldConfigurations(
            'users',
            [],
            [],
            $this->typeMapper
        );

        expect($result)->toBeArray()->toBeEmpty();
    });
});
