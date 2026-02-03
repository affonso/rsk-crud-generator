<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Rsk\CrudGenerator\CrudGeneratorServiceProvider;

/*
|--------------------------------------------------------------------------
| Test Configuration
|--------------------------------------------------------------------------
*/

uses()
    ->beforeEach(function () {
        // Store paths for cleanup
        $this->generatedFiles = [];
        $this->generatedDirectories = [];

        // Setup in-memory SQLite database
        config(['database.default' => 'testing']);
        config(['database.connections.testing' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        // Create test tables
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->timestamps();
        });

        Schema::create('test_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content')->nullable();
            $table->foreignId('category_id')->constrained('categories');
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });

        // Ensure models directory exists in the test app
        $modelsPath = app_path('Models');
        if (! File::isDirectory($modelsPath)) {
            File::makeDirectory($modelsPath, 0755, true);
            $this->generatedDirectories[] = $modelsPath;
        }

        // Create Category model file
        $categoryModelContent = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'categories';
    protected $fillable = ['name', 'slug'];
}
PHP;

        $categoryModelPath = app_path('Models/Category.php');
        File::put($categoryModelPath, $categoryModelContent);
        $this->generatedFiles[] = $categoryModelPath;

        // Create TestPost model file with BelongsTo relationship
        $testPostModelContent = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestPost extends Model
{
    protected $table = 'test_posts';
    protected $fillable = ['title', 'content', 'category_id', 'is_published'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
PHP;

        $testPostModelPath = app_path('Models/TestPost.php');
        File::put($testPostModelPath, $testPostModelContent);
        $this->generatedFiles[] = $testPostModelPath;

        // Load the model classes
        if (! class_exists('App\Models\Category')) {
            require_once $categoryModelPath;
        }
        if (! class_exists('App\Models\TestPost')) {
            require_once $testPostModelPath;
        }
    })
    ->afterEach(function () {
        // Clean up generated CRUD files
        $filesToClean = [
            app_path('Http/Controllers/TestPostController.php'),
            resource_path('js/pages/TestPosts/Index.tsx'),
            resource_path('js/pages/TestPosts/FormDialog.tsx'),
            resource_path('js/pages/TestPosts/columns.tsx'),
            resource_path('js/pages/TestPosts/DataTable.tsx'),
            resource_path('js/types/models/testPost.ts'),
            app_path('Http/Requests/TestPost/StoreTestPostRequest.php'),
            app_path('Http/Requests/TestPost/UpdateTestPostRequest.php'),
        ];

        foreach ($filesToClean as $file) {
            if (File::exists($file)) {
                File::delete($file);
            }
        }

        // Clean up generated directories (only if empty)
        $dirsToClean = [
            resource_path('js/pages/TestPosts'),
            resource_path('js/types/models'),
            app_path('Http/Requests/TestPost'),
        ];

        foreach ($dirsToClean as $dir) {
            if (File::isDirectory($dir)) {
                // Check if directory is empty
                $files = File::files($dir);
                $dirs = File::directories($dir);
                if (empty($files) && empty($dirs)) {
                    File::deleteDirectory($dir);
                }
            }
        }

        // Clean up test model files
        foreach ($this->generatedFiles as $file) {
            if (File::exists($file)) {
                File::delete($file);
            }
        }

        // Clean up test directories (only if empty)
        foreach ($this->generatedDirectories as $dir) {
            if (File::isDirectory($dir)) {
                $files = File::files($dir);
                $dirs = File::directories($dir);
                if (empty($files) && empty($dirs)) {
                    File::deleteDirectory($dir);
                }
            }
        }

        // Drop test tables
        Schema::dropIfExists('test_posts');
        Schema::dropIfExists('categories');
    })
    ->in(__DIR__);

/*
|--------------------------------------------------------------------------
| Helper to get package providers
|--------------------------------------------------------------------------
*/

function getPackageProviders($app): array
{
    return [
        CrudGeneratorServiceProvider::class,
    ];
}

/*
|--------------------------------------------------------------------------
| Test Cases - Command Execution
|--------------------------------------------------------------------------
*/

describe('make:shadcn-crud command execution', function () {
    it('runs successfully for a valid model', function () {
        $this->artisan('make:shadcn-crud', ['model' => 'TestPost', '--force' => true])
            ->assertSuccessful();
    });

    it('fails when model does not exist', function () {
        $this->artisan('make:shadcn-crud', ['model' => 'NonExistentModel'])
            ->assertFailed();
    });

    it('generates controller file', function () {
        $this->artisan('make:shadcn-crud', ['model' => 'TestPost', '--force' => true])
            ->assertSuccessful();

        $controllerPath = app_path('Http/Controllers/TestPostController.php');
        expect(File::exists($controllerPath))->toBeTrue();
    });

    it('generates TypeScript types file', function () {
        $this->artisan('make:shadcn-crud', ['model' => 'TestPost', '--force' => true])
            ->assertSuccessful();

        $typesPath = resource_path('js/types/models/testPost.ts');
        expect(File::exists($typesPath))->toBeTrue();
    });

    it('generates all expected page files', function () {
        $this->artisan('make:shadcn-crud', ['model' => 'TestPost', '--force' => true])
            ->assertSuccessful();

        $expectedFiles = [
            resource_path('js/pages/TestPosts/Index.tsx'),
            resource_path('js/pages/TestPosts/FormDialog.tsx'),
            resource_path('js/pages/TestPosts/columns.tsx'),
            resource_path('js/pages/TestPosts/DataTable.tsx'),
        ];

        foreach ($expectedFiles as $file) {
            expect(File::exists($file))->toBeTrue("Expected file not found: {$file}");
        }
    });
});

/*
|--------------------------------------------------------------------------
| Test Cases - Generated Controller Content
|--------------------------------------------------------------------------
*/

describe('generated controller content', function () {
    it('has correct class name and namespace', function () {
        $this->artisan('make:shadcn-crud', ['model' => 'TestPost', '--force' => true])
            ->assertSuccessful();

        $controllerPath = app_path('Http/Controllers/TestPostController.php');
        $content = File::get($controllerPath);

        expect($content)->toContain('namespace App\Http\Controllers;')
            ->and($content)->toContain('class TestPostController extends Controller');
    });

    it('has all CRUD methods', function () {
        $this->artisan('make:shadcn-crud', ['model' => 'TestPost', '--force' => true])
            ->assertSuccessful();

        $controllerPath = app_path('Http/Controllers/TestPostController.php');
        $content = File::get($controllerPath);

        expect($content)->toContain('public function index()')
            ->and($content)->toContain('public function store(Request $request)')
            ->and($content)->toContain('public function update(Request $request, TestPost $testPost)')
            ->and($content)->toContain('public function destroy(TestPost $testPost)');
    });

    it('includes model import', function () {
        $this->artisan('make:shadcn-crud', ['model' => 'TestPost', '--force' => true])
            ->assertSuccessful();

        $controllerPath = app_path('Http/Controllers/TestPostController.php');
        $content = File::get($controllerPath);

        expect($content)->toContain('use App\Models\TestPost;');
    });

    it('includes validation rules for fillable fields', function () {
        $this->artisan('make:shadcn-crud', ['model' => 'TestPost', '--force' => true])
            ->assertSuccessful();

        $controllerPath = app_path('Http/Controllers/TestPostController.php');
        $content = File::get($controllerPath);

        expect($content)->toContain("'title'")
            ->and($content)->toContain("'content'")
            ->and($content)->toContain("'category_id'")
            ->and($content)->toContain("'is_published'");
    });

    it('includes eager loading for BelongsTo relationships', function () {
        $this->artisan('make:shadcn-crud', ['model' => 'TestPost', '--force' => true])
            ->assertSuccessful();

        $controllerPath = app_path('Http/Controllers/TestPostController.php');
        $content = File::get($controllerPath);

        expect($content)->toContain("with(['category'])")
            ->and($content)->toContain('use App\Models\Category;');
    });
});

/*
|--------------------------------------------------------------------------
| Test Cases - Generated TypeScript Types Content
|--------------------------------------------------------------------------
*/

describe('generated TypeScript types content', function () {
    it('has correct interface name', function () {
        $this->artisan('make:shadcn-crud', ['model' => 'TestPost', '--force' => true])
            ->assertSuccessful();

        $typesPath = resource_path('js/types/models/testPost.ts');
        $content = File::get($typesPath);

        expect($content)->toContain('export interface TestPost {');
    });

    it('includes all model fields with correct types', function () {
        $this->artisan('make:shadcn-crud', ['model' => 'TestPost', '--force' => true])
            ->assertSuccessful();

        $typesPath = resource_path('js/types/models/testPost.ts');
        $content = File::get($typesPath);

        expect($content)->toContain('id: number;')
            ->and($content)->toContain('title: string;')
            ->and($content)->toContain('content: string;')
            ->and($content)->toContain('category_id: number;')
            ->and($content)->toContain('is_published: boolean;');
    });

    it('includes paginated interface', function () {
        $this->artisan('make:shadcn-crud', ['model' => 'TestPost', '--force' => true])
            ->assertSuccessful();

        $typesPath = resource_path('js/types/models/testPost.ts');
        $content = File::get($typesPath);

        expect($content)->toContain('export interface TestPostPaginated {')
            ->and($content)->toContain('data: TestPost[];')
            ->and($content)->toContain('links:')
            ->and($content)->toContain('meta:');
    });

    it('includes FormData type', function () {
        $this->artisan('make:shadcn-crud', ['model' => 'TestPost', '--force' => true])
            ->assertSuccessful();

        $typesPath = resource_path('js/types/models/testPost.ts');
        $content = File::get($typesPath);

        expect($content)->toContain('export type TestPostFormData =');
    });

    it('includes relationship type import for BelongsTo', function () {
        $this->artisan('make:shadcn-crud', ['model' => 'TestPost', '--force' => true])
            ->assertSuccessful();

        $typesPath = resource_path('js/types/models/testPost.ts');
        $content = File::get($typesPath);

        expect($content)->toContain("import type { Category } from '@/types/models/category';");
    });
});

/*
|--------------------------------------------------------------------------
| Test Cases - Generated Form Dialog Content
|--------------------------------------------------------------------------
*/

describe('generated form dialog content', function () {
    it('includes Select component for BelongsTo relationship', function () {
        $this->artisan('make:shadcn-crud', ['model' => 'TestPost', '--force' => true])
            ->assertSuccessful();

        $formDialogPath = resource_path('js/pages/TestPosts/FormDialog.tsx');
        $content = File::get($formDialogPath);

        expect($content)->toContain('Select')
            ->and($content)->toContain('SelectContent')
            ->and($content)->toContain('SelectItem')
            ->and($content)->toContain('SelectTrigger')
            ->and($content)->toContain('SelectValue');
    });

    it('includes form fields for all fillable attributes', function () {
        $this->artisan('make:shadcn-crud', ['model' => 'TestPost', '--force' => true])
            ->assertSuccessful();

        $formDialogPath = resource_path('js/pages/TestPosts/FormDialog.tsx');
        $content = File::get($formDialogPath);

        expect($content)->toContain('Title')
            ->and($content)->toContain('Content')
            ->and($content)->toContain('Category');
    });
});

/*
|--------------------------------------------------------------------------
| Test Cases - Form Requests Option
|--------------------------------------------------------------------------
*/

describe('--with-requests option', function () {
    it('generates form request files when option is provided', function () {
        $this->artisan('make:shadcn-crud', [
            'model' => 'TestPost',
            '--force' => true,
            '--with-requests' => true,
        ])->assertSuccessful();

        $storeRequestPath = app_path('Http/Requests/TestPost/StoreTestPostRequest.php');
        $updateRequestPath = app_path('Http/Requests/TestPost/UpdateTestPostRequest.php');

        expect(File::exists($storeRequestPath))->toBeTrue()
            ->and(File::exists($updateRequestPath))->toBeTrue();
    });

    it('does not generate form requests without the option', function () {
        $this->artisan('make:shadcn-crud', ['model' => 'TestPost', '--force' => true])
            ->assertSuccessful();

        $storeRequestPath = app_path('Http/Requests/TestPost/StoreTestPostRequest.php');

        expect(File::exists($storeRequestPath))->toBeFalse();
    });
});
