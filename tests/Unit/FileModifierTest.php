<?php

declare(strict_types=1);

use App\Support\FileModifier;

// Helper to create a temp file with content
function createTempFile(string $content): string
{
    $path = tempnam(sys_get_temp_dir(), 'fm_test_');
    file_put_contents($path, $content);

    return $path;
}

afterEach(function () {
    // Clean up any temp files created during tests
    foreach (glob(sys_get_temp_dir().'/fm_test_*') as $file) {
        @unlink($file);
    }
});

// --- injectAfter ---

test('injectAfter inserts content after search string', function () {
    $path = createTempFile("line1\nline2\nline3");

    $result = FileModifier::injectAfter($path, "line2\n", "injected\n");

    expect($result)->toBeTrue();
    expect(file_get_contents($path))->toBe("line1\nline2\ninjected\nline3");
});

test('injectAfter is idempotent', function () {
    $path = createTempFile("line1\nline2\nline3");

    FileModifier::injectAfter($path, "line2\n", "injected\n");
    $result = FileModifier::injectAfter($path, "line2\n", "injected\n");

    expect($result)->toBeFalse();
    expect(substr_count(file_get_contents($path), 'injected'))->toBe(1);
});

test('injectAfter returns false if search string not found', function () {
    $path = createTempFile("line1\nline2");

    $result = FileModifier::injectAfter($path, 'notfound', 'injected');

    expect($result)->toBeFalse();
});

test('injectAfter returns false for missing file', function () {
    $result = FileModifier::injectAfter('/nonexistent/path.php', 'search', 'inject');

    expect($result)->toBeFalse();
});

// --- injectBefore ---

test('injectBefore inserts content before search string', function () {
    $path = createTempFile("line1\nline2\nline3");

    $result = FileModifier::injectBefore($path, 'line2', 'injected_');

    expect($result)->toBeTrue();
    expect(file_get_contents($path))->toContain('injected_line2');
});

test('injectBefore is idempotent', function () {
    $path = createTempFile("line1\nline2\nline3");

    FileModifier::injectBefore($path, 'line2', 'injected_');
    $result = FileModifier::injectBefore($path, 'line2', 'injected_');

    expect($result)->toBeFalse();
});

test('injectBefore returns false if search string not found', function () {
    $path = createTempFile("line1\nline2");

    $result = FileModifier::injectBefore($path, 'notfound', 'injected');

    expect($result)->toBeFalse();
});

test('injectBefore returns false for missing file', function () {
    $result = FileModifier::injectBefore('/nonexistent/path.php', 'search', 'inject');

    expect($result)->toBeFalse();
});

// --- replace ---

test('replace replaces target with replacement', function () {
    $path = createTempFile("Hello World");

    $result = FileModifier::replace($path, 'World', 'PHP');

    expect($result)->toBeTrue();
    expect(file_get_contents($path))->toBe('Hello PHP');
});

test('replace is idempotent when target no longer exists', function () {
    $path = createTempFile("Hello World");

    FileModifier::replace($path, 'World', 'PHP');
    $result = FileModifier::replace($path, 'World', 'PHP');

    expect($result)->toBeFalse();
    expect(file_get_contents($path))->toBe('Hello PHP');
});

test('replace returns false if target not found', function () {
    $path = createTempFile("Hello World");

    $result = FileModifier::replace($path, 'NotFound', 'New');

    expect($result)->toBeFalse();
});

test('replace returns false for missing file', function () {
    $result = FileModifier::replace('/nonexistent/path.php', 'a', 'b');

    expect($result)->toBeFalse();
});

// --- addUseStatement ---

test('addUseStatement adds use statement after existing ones', function () {
    $content = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
}
PHP;
    $path = createTempFile($content);

    $result = FileModifier::addUseStatement($path, 'App\Traits\MyTrait');

    expect($result)->toBeTrue();
    expect(file_get_contents($path))->toContain('use App\Traits\MyTrait;');
});

test('addUseStatement is idempotent', function () {
    $content = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
}
PHP;
    $path = createTempFile($content);

    FileModifier::addUseStatement($path, 'App\Traits\MyTrait');
    $result = FileModifier::addUseStatement($path, 'App\Traits\MyTrait');

    expect($result)->toBeFalse();
    expect(substr_count(file_get_contents($path), 'use App\Traits\MyTrait;'))->toBe(1);
});

test('addUseStatement adds after namespace if no use statements exist', function () {
    $content = <<<'PHP'
<?php

namespace App\Models;

class User
{
}
PHP;
    $path = createTempFile($content);

    $result = FileModifier::addUseStatement($path, 'App\Traits\MyTrait');

    expect($result)->toBeTrue();
    expect(file_get_contents($path))->toContain("namespace App\Models;\n\nuse App\Traits\MyTrait;");
});

test('addUseStatement returns false for missing file', function () {
    $result = FileModifier::addUseStatement('/nonexistent/path.php', 'MyClass');

    expect($result)->toBeFalse();
});

// --- addTrait ---

test('addTrait adds trait inside class body', function () {
    $content = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $fillable = ['name'];
}
PHP;
    $path = createTempFile($content);

    $result = FileModifier::addTrait($path, 'HasRoles');

    expect($result)->toBeTrue();
    expect(file_get_contents($path))->toContain('use HasRoles;');
});

test('addTrait is idempotent', function () {
    $content = <<<'PHP'
<?php

namespace App\Models;

class User
{
    use HasRoles;
}
PHP;
    $path = createTempFile($content);

    $result = FileModifier::addTrait($path, 'HasRoles');

    expect($result)->toBeFalse();
});

test('addTrait returns false if no class found', function () {
    $path = createTempFile("<?php\n\n\$x = 1;\n");

    $result = FileModifier::addTrait($path, 'HasRoles');

    expect($result)->toBeFalse();
});

test('addTrait returns false for missing file', function () {
    $result = FileModifier::addTrait('/nonexistent/path.php', 'MyTrait');

    expect($result)->toBeFalse();
});

// --- appendToFile ---

test('appendToFile adds content to end of file', function () {
    $path = createTempFile("line1\n");

    $result = FileModifier::appendToFile($path, 'appended');

    expect($result)->toBeTrue();
    expect(file_get_contents($path))->toContain('appended');
});

test('appendToFile is idempotent', function () {
    $path = createTempFile("line1\nappended\n");

    $result = FileModifier::appendToFile($path, 'appended');

    expect($result)->toBeFalse();
});

test('appendToFile returns false for missing file', function () {
    $result = FileModifier::appendToFile('/nonexistent/path.php', 'appended');

    expect($result)->toBeFalse();
});

// --- ensureFileExists ---

test('ensureFileExists creates file if missing', function () {
    $path = sys_get_temp_dir().'/fm_test_ensure_'.uniqid().'.txt';

    FileModifier::ensureFileExists($path, 'default content');

    expect(file_exists($path))->toBeTrue();
    expect(file_get_contents($path))->toBe('default content');

    @unlink($path);
});

test('ensureFileExists does not overwrite existing file', function () {
    $path = createTempFile('original');

    FileModifier::ensureFileExists($path, 'new content');

    expect(file_get_contents($path))->toBe('original');

    @unlink($path);
});

test('ensureFileExists creates nested directories', function () {
    $dir = sys_get_temp_dir().'/fm_test_nest_'.uniqid();
    $path = $dir.'/sub/dir/file.txt';

    FileModifier::ensureFileExists($path, 'nest');

    expect(file_exists($path))->toBeTrue();
    expect(file_get_contents($path))->toBe('nest');

    @unlink($path);
    @rmdir($dir.'/sub/dir');
    @rmdir($dir.'/sub');
    @rmdir($dir);
});
