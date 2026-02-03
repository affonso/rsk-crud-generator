<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Rsk\CrudGenerator\Contracts\ModelIntrospectorInterface;
use Rsk\CrudGenerator\Services\RelationshipDetector;

beforeEach(function () {
    $this->modelIntrospector = Mockery::mock(ModelIntrospectorInterface::class);
    $this->detector = new RelationshipDetector($this->modelIntrospector);
});

afterEach(function () {
    Mockery::close();
});

describe('detectRelationships', function () {
    it('detects BelongsTo relationships', function () {
        // Create a mock User model class name
        $relatedUserClass = get_class(new class extends Model
        {
            protected $table = 'users';

            public function getTable(): string
            {
                return 'users';
            }
        });

        // Create a test model with a BelongsTo relationship
        $testModel = new class($relatedUserClass) extends Model
        {
            protected $table = 'posts';

            public string $relatedUserClass;

            public function __construct(string $relatedUserClass = '')
            {
                $this->relatedUserClass = $relatedUserClass;
                parent::__construct();
            }

            public function user(): BelongsTo
            {
                return $this->belongsTo($this->relatedUserClass, 'user_id');
            }
        };

        $this->modelIntrospector->shouldReceive('guessDisplayField')
            ->once()
            ->with('users')
            ->andReturn('name');

        $result = $this->detector->detectRelationships($testModel, get_class($testModel));

        expect($result)->toBeArray()
            ->toHaveKeys(['belongsTo', 'hasMany', 'belongsToMany'])
            ->and($result['belongsTo'])->toHaveCount(1)
            ->and($result['belongsTo'][0])->toHaveKeys([
                'method',
                'foreignKey',
                'relatedModel',
                'relatedModelClass',
                'relatedTable',
                'displayField',
            ])
            ->and($result['belongsTo'][0]['method'])->toBe('user')
            ->and($result['belongsTo'][0]['foreignKey'])->toBe('user_id')
            ->and($result['belongsTo'][0]['relatedTable'])->toBe('users')
            ->and($result['belongsTo'][0]['displayField'])->toBe('name')
            ->and($result['hasMany'])->toBeEmpty()
            ->and($result['belongsToMany'])->toBeEmpty();
    });

    it('detects HasMany relationships', function () {
        // Create a mock Post model class name
        $relatedPostClass = get_class(new class extends Model
        {
            protected $table = 'posts';

            public function getTable(): string
            {
                return 'posts';
            }
        });

        // Create a test model with a HasMany relationship
        $testModel = new class($relatedPostClass) extends Model
        {
            protected $table = 'users';

            public string $relatedPostClass;

            public function __construct(string $relatedPostClass = '')
            {
                $this->relatedPostClass = $relatedPostClass;
                parent::__construct();
            }

            public function posts(): HasMany
            {
                return $this->hasMany($this->relatedPostClass, 'user_id');
            }
        };

        $result = $this->detector->detectRelationships($testModel, get_class($testModel));

        expect($result)->toBeArray()
            ->and($result['hasMany'])->toHaveCount(1)
            ->and($result['hasMany'][0])->toHaveKeys([
                'method',
                'relatedModel',
                'foreignKey',
            ])
            ->and($result['hasMany'][0]['method'])->toBe('posts')
            ->and($result['hasMany'][0]['foreignKey'])->toBe('user_id')
            ->and($result['belongsTo'])->toBeEmpty()
            ->and($result['belongsToMany'])->toBeEmpty();
    });

    it('detects BelongsToMany relationships', function () {
        // Create a mock Role model class name
        $relatedRoleClass = get_class(new class extends Model
        {
            protected $table = 'roles';

            public function getTable(): string
            {
                return 'roles';
            }
        });

        // Create a test model with a BelongsToMany relationship
        $testModel = new class($relatedRoleClass) extends Model
        {
            protected $table = 'users';

            public string $relatedRoleClass;

            public function __construct(string $relatedRoleClass = '')
            {
                $this->relatedRoleClass = $relatedRoleClass;
                parent::__construct();
            }

            public function roles(): BelongsToMany
            {
                return $this->belongsToMany($this->relatedRoleClass, 'role_user', 'user_id', 'role_id');
            }
        };

        $result = $this->detector->detectRelationships($testModel, get_class($testModel));

        expect($result)->toBeArray()
            ->and($result['belongsToMany'])->toHaveCount(1)
            ->and($result['belongsToMany'][0])->toHaveKeys([
                'method',
                'relatedModel',
                'pivotTable',
            ])
            ->and($result['belongsToMany'][0]['method'])->toBe('roles')
            ->and($result['belongsToMany'][0]['pivotTable'])->toBe('role_user')
            ->and($result['belongsTo'])->toBeEmpty()
            ->and($result['hasMany'])->toBeEmpty();
    });

    it('detects multiple relationship types on the same model', function () {
        $relatedUserClass = get_class(new class extends Model
        {
            protected $table = 'users';

            public function getTable(): string
            {
                return 'users';
            }
        });

        $relatedCommentClass = get_class(new class extends Model
        {
            protected $table = 'comments';

            public function getTable(): string
            {
                return 'comments';
            }
        });

        $relatedTagClass = get_class(new class extends Model
        {
            protected $table = 'tags';

            public function getTable(): string
            {
                return 'tags';
            }
        });

        // Create a test model with multiple relationship types
        $testModel = new class($relatedUserClass, $relatedCommentClass, $relatedTagClass) extends Model
        {
            protected $table = 'posts';

            public string $relatedUserClass;

            public string $relatedCommentClass;

            public string $relatedTagClass;

            public function __construct(string $relatedUserClass = '', string $relatedCommentClass = '', string $relatedTagClass = '')
            {
                $this->relatedUserClass = $relatedUserClass;
                $this->relatedCommentClass = $relatedCommentClass;
                $this->relatedTagClass = $relatedTagClass;
                parent::__construct();
            }

            public function user(): BelongsTo
            {
                return $this->belongsTo($this->relatedUserClass, 'user_id');
            }

            public function comments(): HasMany
            {
                return $this->hasMany($this->relatedCommentClass, 'post_id');
            }

            public function tags(): BelongsToMany
            {
                return $this->belongsToMany($this->relatedTagClass, 'post_tag', 'post_id', 'tag_id');
            }
        };

        $this->modelIntrospector->shouldReceive('guessDisplayField')
            ->once()
            ->with('users')
            ->andReturn('name');

        $result = $this->detector->detectRelationships($testModel, get_class($testModel));

        expect($result)->toBeArray()
            ->and($result['belongsTo'])->toHaveCount(1)
            ->and($result['belongsTo'][0]['method'])->toBe('user')
            ->and($result['hasMany'])->toHaveCount(1)
            ->and($result['hasMany'][0]['method'])->toBe('comments')
            ->and($result['belongsToMany'])->toHaveCount(1)
            ->and($result['belongsToMany'][0]['method'])->toBe('tags');
    });

    it('ignores non-relationship methods', function () {
        // Create a test model with regular methods that return other types
        $testModel = new class extends Model
        {
            protected $table = 'users';

            public function getFullName(): string
            {
                return 'John Doe';
            }

            public function isActive(): bool
            {
                return true;
            }

            public function getAge(): int
            {
                return 25;
            }

            public function getData(): array
            {
                return ['key' => 'value'];
            }
        };

        $result = $this->detector->detectRelationships($testModel, get_class($testModel));

        expect($result)->toBeArray()
            ->and($result['belongsTo'])->toBeEmpty()
            ->and($result['hasMany'])->toBeEmpty()
            ->and($result['belongsToMany'])->toBeEmpty();
    });

    it('ignores inherited methods from Model class', function () {
        // Create a simple test model without any custom methods
        $testModel = new class extends Model
        {
            protected $table = 'users';
        };

        $result = $this->detector->detectRelationships($testModel, get_class($testModel));

        expect($result)->toBeArray()
            ->and($result['belongsTo'])->toBeEmpty()
            ->and($result['hasMany'])->toBeEmpty()
            ->and($result['belongsToMany'])->toBeEmpty();
    });

    it('ignores methods with required parameters', function () {
        $relatedUser = new class extends Model
        {
            protected $table = 'users';

            public function getTable(): string
            {
                return 'users';
            }
        };

        // Create a test model with a method that has required parameters
        $testModel = new class($relatedUser) extends Model
        {
            protected $table = 'posts';

            private ?Model $relatedUser = null;

            public function __construct(?Model $relatedUser = null)
            {
                $this->relatedUser = $relatedUser;
                parent::__construct();
            }

            // This should be ignored because it has a required parameter
            public function userWithScope(string $scope): BelongsTo
            {
                return $this->belongsTo(get_class($this->relatedUser), 'user_id');
            }

            // This should be detected (no required parameters)
            public function author(): BelongsTo
            {
                return $this->belongsTo(get_class($this->relatedUser), 'author_id');
            }
        };

        $this->modelIntrospector->shouldReceive('guessDisplayField')
            ->once()
            ->with('users')
            ->andReturn('name');

        $result = $this->detector->detectRelationships($testModel, get_class($testModel));

        expect($result)->toBeArray()
            ->and($result['belongsTo'])->toHaveCount(1)
            ->and($result['belongsTo'][0]['method'])->toBe('author')
            ->and($result['belongsTo'][0]['foreignKey'])->toBe('author_id');
    });

    it('ignores methods that throw exceptions', function () {
        $relatedUser = new class extends Model
        {
            protected $table = 'users';

            public function getTable(): string
            {
                return 'users';
            }
        };

        // Create a test model with a method that throws an exception
        $testModel = new class($relatedUser) extends Model
        {
            protected $table = 'posts';

            private ?Model $relatedUser = null;

            public function __construct(?Model $relatedUser = null)
            {
                $this->relatedUser = $relatedUser;
                parent::__construct();
            }

            public function brokenRelationship(): BelongsTo
            {
                throw new \Exception('This method throws an exception');
            }

            public function validRelationship(): BelongsTo
            {
                return $this->belongsTo(get_class($this->relatedUser), 'user_id');
            }
        };

        $this->modelIntrospector->shouldReceive('guessDisplayField')
            ->once()
            ->with('users')
            ->andReturn('name');

        $result = $this->detector->detectRelationships($testModel, get_class($testModel));

        // Should only detect the valid relationship, ignoring the broken one
        expect($result)->toBeArray()
            ->and($result['belongsTo'])->toHaveCount(1)
            ->and($result['belongsTo'][0]['method'])->toBe('validRelationship');
    });

    it('returns empty arrays when model has no relationships', function () {
        $testModel = new class extends Model
        {
            protected $table = 'simple_table';

            public function someMethod(): string
            {
                return 'not a relationship';
            }
        };

        $result = $this->detector->detectRelationships($testModel, get_class($testModel));

        expect($result)->toBeArray()
            ->toHaveKeys(['belongsTo', 'hasMany', 'belongsToMany'])
            ->and($result['belongsTo'])->toBeEmpty()
            ->and($result['hasMany'])->toBeEmpty()
            ->and($result['belongsToMany'])->toBeEmpty();
    });

    it('extracts correct related model class name', function () {
        $relatedUser = new class extends Model
        {
            protected $table = 'users';

            public function getTable(): string
            {
                return 'users';
            }
        };

        $testModel = new class($relatedUser) extends Model
        {
            protected $table = 'posts';

            private ?Model $relatedUser = null;

            public function __construct(?Model $relatedUser = null)
            {
                $this->relatedUser = $relatedUser;
                parent::__construct();
            }

            public function user(): BelongsTo
            {
                return $this->belongsTo(get_class($this->relatedUser), 'user_id');
            }
        };

        $this->modelIntrospector->shouldReceive('guessDisplayField')
            ->once()
            ->with('users')
            ->andReturn('name');

        $result = $this->detector->detectRelationships($testModel, get_class($testModel));

        expect($result['belongsTo'][0]['relatedModel'])->toBeString()
            ->and($result['belongsTo'][0]['relatedModelClass'])->toBe(get_class($relatedUser));
    });
});

describe('mapForeignKeysToRelationships', function () {
    it('maps foreign keys from BelongsTo relationships', function () {
        $relationships = [
            'belongsTo' => [
                [
                    'method' => 'user',
                    'foreignKey' => 'user_id',
                    'relatedModel' => 'User',
                    'relatedModelClass' => 'App\\Models\\User',
                    'relatedTable' => 'users',
                    'displayField' => 'name',
                ],
                [
                    'method' => 'category',
                    'foreignKey' => 'category_id',
                    'relatedModel' => 'Category',
                    'relatedModelClass' => 'App\\Models\\Category',
                    'relatedTable' => 'categories',
                    'displayField' => 'name',
                ],
            ],
            'hasMany' => [],
            'belongsToMany' => [],
        ];

        $result = $this->detector->mapForeignKeysToRelationships($relationships);

        expect($result)->toBeArray()
            ->toHaveCount(2)
            ->toHaveKey('user_id')
            ->toHaveKey('category_id')
            ->and($result['user_id'])->toBe($relationships['belongsTo'][0])
            ->and($result['category_id'])->toBe($relationships['belongsTo'][1])
            ->and($result['user_id']['method'])->toBe('user')
            ->and($result['category_id']['method'])->toBe('category');
    });

    it('handles empty relationships array', function () {
        $relationships = [
            'belongsTo' => [],
            'hasMany' => [],
            'belongsToMany' => [],
        ];

        $result = $this->detector->mapForeignKeysToRelationships($relationships);

        expect($result)->toBeArray()->toBeEmpty();
    });

    it('only maps belongsTo relationships, ignoring others', function () {
        $relationships = [
            'belongsTo' => [
                [
                    'method' => 'user',
                    'foreignKey' => 'user_id',
                    'relatedModel' => 'User',
                    'relatedModelClass' => 'App\\Models\\User',
                    'relatedTable' => 'users',
                    'displayField' => 'name',
                ],
            ],
            'hasMany' => [
                [
                    'method' => 'posts',
                    'foreignKey' => 'user_id',
                    'relatedModel' => 'Post',
                ],
            ],
            'belongsToMany' => [
                [
                    'method' => 'roles',
                    'relatedModel' => 'Role',
                    'pivotTable' => 'role_user',
                ],
            ],
        ];

        $result = $this->detector->mapForeignKeysToRelationships($relationships);

        expect($result)->toBeArray()
            ->toHaveCount(1)
            ->toHaveKey('user_id')
            ->and($result['user_id']['method'])->toBe('user');
    });

    it('maps single belongsTo relationship', function () {
        $relationships = [
            'belongsTo' => [
                [
                    'method' => 'author',
                    'foreignKey' => 'author_id',
                    'relatedModel' => 'User',
                    'relatedModelClass' => 'App\\Models\\User',
                    'relatedTable' => 'users',
                    'displayField' => 'name',
                ],
            ],
            'hasMany' => [],
            'belongsToMany' => [],
        ];

        $result = $this->detector->mapForeignKeysToRelationships($relationships);

        expect($result)->toBeArray()
            ->toHaveCount(1)
            ->toHaveKey('author_id')
            ->and($result['author_id'])->toHaveKeys([
                'method',
                'foreignKey',
                'relatedModel',
                'relatedModelClass',
                'relatedTable',
                'displayField',
            ])
            ->and($result['author_id']['foreignKey'])->toBe('author_id');
    });

    it('preserves all relationship data in the map', function () {
        $expectedRelationship = [
            'method' => 'company',
            'foreignKey' => 'company_id',
            'relatedModel' => 'Company',
            'relatedModelClass' => 'App\\Models\\Company',
            'relatedTable' => 'companies',
            'displayField' => 'name',
        ];

        $relationships = [
            'belongsTo' => [$expectedRelationship],
            'hasMany' => [],
            'belongsToMany' => [],
        ];

        $result = $this->detector->mapForeignKeysToRelationships($relationships);

        expect($result['company_id'])->toBe($expectedRelationship)
            ->and($result['company_id']['method'])->toBe('company')
            ->and($result['company_id']['foreignKey'])->toBe('company_id')
            ->and($result['company_id']['relatedModel'])->toBe('Company')
            ->and($result['company_id']['relatedModelClass'])->toBe('App\\Models\\Company')
            ->and($result['company_id']['relatedTable'])->toBe('companies')
            ->and($result['company_id']['displayField'])->toBe('name');
    });
});
