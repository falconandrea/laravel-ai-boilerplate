<?php

declare(strict_types=1);

use App\Commands\InstallCommand;

test('install command runs full interactive flow successfully', function () {
    $dir = createTempProject();
    $commands = [];
    mockProcesses(true, $commands);
    
    // Command-level prompts
    fakeCommandPrompts([
        ['scaffold', 'sail'], // Components
        true,                 // Confirm
    ]);

    // Create a dummy artisan file to simulate a Laravel project
    touch($dir.'/artisan');

    $this->artisan('install', ['path' => $dir])
        ->assertExitCode(0);

    // Verify installers were called
    expect($commands)->toContain('composer require laravel/boost --dev --no-interaction');
    expect($commands)->not->toContain('php artisan boost:install');
    expect($commands)->toContain('composer require laravel/sail --dev --no-interaction');
    expect($commands)->toContain('php artisan sail:install --with=mysql,redis,meilisearch,mailpit,selenium');

    deleteTempProject($dir);
});

test('install command handles bootstrap mode for new projects', function () {
    $dir = createTempProject(); // empty dir, no artisan
    $commands = [];
    mockProcesses(true, $commands);
    
    // Command-level prompts
    fakeCommandPrompts([
        'my-new-app',
        ['scaffold'],
        true,
    ]);

    $this->artisan('install', ['path' => $dir])
        ->assertExitCode(0);

    // Verify composer create-project was called
    $foundCreateProject = false;
    foreach ($commands as $cmd) {
        if (str_contains($cmd, 'composer create-project')) {
            $foundCreateProject = true;
            break;
        }
    }
    expect($foundCreateProject)->toBeTrue();

    deleteTempProject($dir);
});

test('install command exits if no components selected', function () {
    $dir = createTempProject();
    touch($dir.'/artisan');
    
    fakeCommandPrompts([
        [], // no selection
    ]);

    $this->artisan('install', ['path' => $dir])
        ->assertExitCode(0);

    deleteTempProject($dir);
});

test('install command exits if installation cancelled', function () {
    $dir = createTempProject();
    touch($dir.'/artisan');
    
    fakeCommandPrompts([
        ['scaffold'],
        false // cancel
    ]);

    $this->artisan('install', ['path' => $dir])
        ->assertExitCode(0);

    deleteTempProject($dir);
});
