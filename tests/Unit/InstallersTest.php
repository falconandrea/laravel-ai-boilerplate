<?php

declare(strict_types=1);

use App\Installers\SailInstaller;
use App\Installers\TelescopeInstaller;
use App\Installers\SanctumInstaller;
use App\Installers\SpatieActivitylogInstaller;
use App\Installers\SpatiePermissionInstaller;
use App\Installers\LivewireInstaller;
use App\Installers\FilamentInstaller;
use App\Installers\BreezeInstaller;
use App\Installers\ExcelInstaller;
use App\Installers\QueuesInstaller;

// --- Installer name checks ---

test('all installers have non-empty names', function (string $class) {
    $installer = new $class('/tmp');

    expect($installer->name())->toBeString()->not->toBeEmpty();
})->with([
    SailInstaller::class,
    TelescopeInstaller::class,
    SanctumInstaller::class,
    SpatieActivitylogInstaller::class,
    SpatiePermissionInstaller::class,
    LivewireInstaller::class,
    FilamentInstaller::class,
    BreezeInstaller::class,
    ExcelInstaller::class,
    QueuesInstaller::class,
]);

// --- Success & Failure logic tests using mockProcesses ---

test('sail installer runs composer for installation', function () {
    $dir = createTempProject();
    $commands = [];
    mockProcesses(true, $commands);

    $installer = new SailInstaller($dir);
    $result = $installer->install();

    expect($result['success'])->toBeTrue();
    expect($commands)->toContain('composer require laravel/sail --dev --no-interaction');
    expect($commands)->toContain('php artisan sail:install --with=mysql,redis,meilisearch,mailpit,selenium');

    deleteTempProject($dir);
});

test('filament installer handles command failure', function () {
    $dir = createTempProject();
    $commands = [];
    mockProcesses(false, $commands); // Simulate failure

    $installer = new FilamentInstaller($dir);
    $result = $installer->install();

    expect($result['success'])->toBeFalse();
    expect($commands)->toContain('composer require filament/filament:"^3.0" --no-interaction');

    deleteTempProject($dir);
});

test('breeze installer runs migrations and reports success', function () {
    $dir = createTempProject();
    $commands = [];
    mockProcesses(true, $commands);
    fakePrompts(['blade']);

    $installer = new BreezeInstaller($dir);
    $result = $installer->install();

    expect($result['success'])->toBeTrue();
    expect($commands)->toContain('composer require laravel/breeze --dev --no-interaction');
    expect($commands)->toContain('php artisan breeze:install blade');
    expect($commands)->toContain('php artisan migrate --no-interaction');

    deleteTempProject($dir);
});

test('sanctum installer publishes and migrates', function () {
    $dir = createTempProject();
    $commands = [];
    mockProcesses(true, $commands);

    $installer = new SanctumInstaller($dir);
    $result = $installer->install();

    expect($result['success'])->toBeTrue();
    expect($commands)->toContain('php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"');
    expect($commands)->toContain('php artisan migrate --no-interaction');

    deleteTempProject($dir);
});

test('telescope installer installs and migrates', function () {
    $dir = createTempProject();
    $commands = [];
    mockProcesses(true, $commands);

    $installer = new TelescopeInstaller($dir);
    $result = $installer->install();

    expect($result['success'])->toBeTrue();
    expect($commands)->toContain('composer require laravel/telescope --dev --no-interaction');
    expect($commands)->toContain('php artisan telescope:install');
    expect($commands)->toContain('php artisan migrate --no-interaction');

    deleteTempProject($dir);
});

test('spatie activitylog installs and migrates', function () {
    $dir = createTempProject();
    $commands = [];
    mockProcesses(true, $commands);

    $installer = new SpatieActivitylogInstaller($dir);
    $result = $installer->install();

    expect($result['success'])->toBeTrue();
    expect($commands)->toContain('composer require spatie/laravel-activitylog --no-interaction');
    expect($commands)->toContain('php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"');
    expect($commands)->toContain('php artisan migrate --no-interaction');

    deleteTempProject($dir);
});

test('spatie permission installs and migrates', function () {
    $dir = createTempProject();
    $commands = [];
    mockProcesses(true, $commands);

    $installer = new SpatiePermissionInstaller($dir);
    $result = $installer->install();

    expect($result['success'])->toBeTrue();
    expect($commands)->toContain('composer require spatie/laravel-permission --no-interaction');
    expect($commands)->toContain('php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --tag="migrations"');
    expect($commands)->toContain('php artisan migrate --no-interaction');

    deleteTempProject($dir);
});

// --- QueuesInstaller ---

test('queues installer injects schedule entry', function () {
    $dir = createTempProject();
    mkdir($dir.'/routes', 0755, true);

    $installer = new QueuesInstaller($dir);
    $result = $installer->install();

    expect($result['success'])->toBeTrue();
    expect($result['warnings'])->toBeEmpty();

    $consolePath = $dir.'/routes/console.php';
    expect(file_exists($consolePath))->toBeTrue();
    expect(file_get_contents($consolePath))->toContain("queue:prune-failed");

    deleteTempProject($dir);
});

test('livewire installer installs and reports success', function () {
    $dir = createTempProject();
    $commands = [];
    mockProcesses(true, $commands);

    $installer = new LivewireInstaller($dir);
    $result = $installer->install();

    expect($result['success'])->toBeTrue();
    expect($commands)->toContain('composer require livewire/livewire --no-interaction');

    deleteTempProject($dir);
});

test('excel installer installs and reports success', function () {
    $dir = createTempProject();
    $commands = [];
    mockProcesses(true, $commands);

    $installer = new ExcelInstaller($dir);
    $result = $installer->install();

    expect($result['success'])->toBeTrue();
    expect($commands)->toContain('composer require maatwebsite/excel --no-interaction');
    expect($commands)->toContain('php artisan vendor:publish --provider="Maatwebsite\Excel\ExcelServiceProvider" --tag=config');

    deleteTempProject($dir);
});

// --- Negative branches ---

test('installer fails if composer fails', function () {
    $dir = createTempProject();
    $commands = [];
    mockProcesses(false, $commands); // Fail all processes

    $installer = new SanctumInstaller($dir);
    $result = $installer->install();

    expect($result['success'])->toBeFalse();
    expect($result['warnings'][0])->toContain('Failed to install');

    deleteTempProject($dir);
});

test('installer succeeds even if artisan publish fails (with warning)', function () {
    $dir = createTempProject();
    \App\Installers\BaseInstaller::$processRunner = function (array $command) {
        // Fail artisan publish but succeed others
        if (in_array('vendor:publish', $command)) {
            return false;
        }
        return true;
    };

    $installer = new SanctumInstaller($dir);
    $result = $installer->install();

    expect($result['success'])->toBeTrue();
    expect($result['warnings'])->toContain('vendor:publish for Sanctum failed. Run it manually.');

    deleteTempProject($dir);
});

test('installer handles migration failure gracefully', function () {
    $dir = createTempProject();
    \App\Installers\BaseInstaller::$processRunner = function (array $command) {
        if (in_array('migrate', $command)) {
            return false;
        }
        return true;
    };

    $installer = new SanctumInstaller($dir);
    $result = $installer->install();

    expect($result['success'])->toBeTrue();
    expect($result['warnings'][0])->toContain('Migration deferred');

    deleteTempProject($dir);
});

// --- Already Installed detection tests ---

test('installers skip if already installed', function (string $class, string $package, bool $dev = false) {
    $dir = createTempProject();
    $key = $dev ? 'require-dev' : 'require';
    file_put_contents($dir.'/composer.json', json_encode([
        $key => [$package => '^1.0'],
    ]));

    $installer = new $class($dir);
    $result = $installer->install();

    expect($result['success'])->toBeTrue();
    expect($result['warnings'])->not->toBeEmpty();
    expect($result['warnings'][0])->toContain('already installed');

    deleteTempProject($dir);
})->with([
    [SailInstaller::class, 'laravel/sail', true],
    [TelescopeInstaller::class, 'laravel/telescope', true],
    [SanctumInstaller::class, 'laravel/sanctum', false],
    [SpatieActivitylogInstaller::class, 'spatie/laravel-activitylog', false],
    [SpatiePermissionInstaller::class, 'spatie/laravel-permission', false],
    [LivewireInstaller::class, 'livewire/livewire', false],
    [FilamentInstaller::class, 'filament/filament', false],
    [ExcelInstaller::class, 'maatwebsite/excel', false],
    [BreezeInstaller::class, 'laravel/breeze', true],
]);
