<?php

declare(strict_types=1);

use App\Installers\BaseInstaller;

// Concrete stub to test the abstract BaseInstaller
class FakeInstaller extends BaseInstaller
{
    public function name(): string
    {
        return 'Fake Package';
    }

    public function install(): array
    {
        return $this->result(true);
    }

    // Expose protected methods for testing
    public function testAlreadyInstalled(string $package): bool
    {
        return $this->alreadyInstalled($package);
    }

    public function testInjectSchedule(string $code): bool
    {
        return $this->injectSchedule($code);
    }

    public function testModifyFile(string $path, string $search, string $replace): bool
    {
        return $this->modifyFile($path, $search, $replace);
    }

    public function testResult(bool $success, array $warnings = []): array
    {
        return $this->result($success, $warnings);
    }
}

// Helper to set up a fake Laravel project structure
function createFakeProject(): string
{
    $dir = sys_get_temp_dir().'/bi_test_'.uniqid();
    mkdir($dir, 0755, true);
    mkdir($dir.'/routes', 0755, true);

    // Create a minimal composer.json
    file_put_contents($dir.'/composer.json', json_encode([
        'require' => [
            'laravel/framework' => '^13.0',
            'laravel/sanctum' => '^4.0',
        ],
        'require-dev' => [
            'laravel/telescope' => '^5.0',
        ],
    ]));

    // Create artisan file to simulate a Laravel project
    file_put_contents($dir.'/artisan', '<?php // fake artisan');

    return $dir;
}

function cleanupFakeProject(string $dir): void
{
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($dir);
}

afterEach(function () {
    foreach (glob(sys_get_temp_dir().'/bi_test_*') as $dir) {
        if (is_dir($dir)) {
            cleanupFakeProject($dir);
        }
    }
});

// --- alreadyInstalled ---

test('alreadyInstalled returns true for require dependency', function () {
    $projectPath = createFakeProject();
    $installer = new FakeInstaller($projectPath);

    expect($installer->testAlreadyInstalled('laravel/sanctum'))->toBeTrue();
});

test('alreadyInstalled returns true for require-dev dependency', function () {
    $projectPath = createFakeProject();
    $installer = new FakeInstaller($projectPath);

    expect($installer->testAlreadyInstalled('laravel/telescope'))->toBeTrue();
});

test('alreadyInstalled returns false for missing dependency', function () {
    $projectPath = createFakeProject();
    $installer = new FakeInstaller($projectPath);

    expect($installer->testAlreadyInstalled('spatie/laravel-activitylog'))->toBeFalse();
});

test('alreadyInstalled returns false when composer.json missing', function () {
    $dir = sys_get_temp_dir().'/bi_test_'.uniqid();
    mkdir($dir, 0755, true);

    $installer = new FakeInstaller($dir);

    expect($installer->testAlreadyInstalled('anything'))->toBeFalse();
});

// --- result ---

test('result builds correct success array', function () {
    $projectPath = createFakeProject();
    $installer = new FakeInstaller($projectPath);

    $result = $installer->testResult(true);

    expect($result)->toBe(['success' => true, 'warnings' => []]);
});

test('result builds correct failure array with warnings', function () {
    $projectPath = createFakeProject();
    $installer = new FakeInstaller($projectPath);

    $result = $installer->testResult(false, ['Something went wrong']);

    expect($result)->toBe([
        'success' => false,
        'warnings' => ['Something went wrong'],
    ]);
});

// --- injectSchedule ---

test('injectSchedule creates console.php if missing', function () {
    $projectPath = createFakeProject();
    // Remove routes dir and recreate without console.php
    $consolePath = $projectPath.'/routes/console.php';

    $installer = new FakeInstaller($projectPath);
    $installer->testInjectSchedule("Schedule::command('test:run')->daily();");

    expect(file_exists($consolePath))->toBeTrue();
    expect(file_get_contents($consolePath))->toContain("Schedule::command('test:run')->daily();");
    expect(file_get_contents($consolePath))->toContain('use Illuminate\Support\Facades\Schedule;');
});

test('injectSchedule is idempotent', function () {
    $projectPath = createFakeProject();
    $installer = new FakeInstaller($projectPath);

    $installer->testInjectSchedule("Schedule::command('test:run')->daily();");
    $installer->testInjectSchedule("Schedule::command('test:run')->daily();");

    $content = file_get_contents($projectPath.'/routes/console.php');
    expect(substr_count($content, "Schedule::command('test:run')->daily();"))->toBe(1);
});

// --- modifyFile ---

test('modifyFile replaces content in a file', function () {
    $projectPath = createFakeProject();
    file_put_contents($projectPath.'/test.txt', 'Hello World');

    $installer = new FakeInstaller($projectPath);
    $result = $installer->testModifyFile('test.txt', 'World', 'Laravel');

    expect($result)->toBeTrue();
    expect(file_get_contents($projectPath.'/test.txt'))->toBe('Hello Laravel');
});

test('modifyFile returns false if file does not exist', function () {
    $projectPath = createFakeProject();
    $installer = new FakeInstaller($projectPath);

    $result = $installer->testModifyFile('nonexistent.txt', 'a', 'b');

    expect($result)->toBeFalse();
});
