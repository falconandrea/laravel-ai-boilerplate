<?php

declare(strict_types=1);

use App\Commands\InstallCommand;

test('install command runs full interactive flow successfully', function () {
    $dir = createTempProject();
    $commands = [];
    mockProcesses(true, $commands);
    
    // 1. Multiselect: scaffold, sail
    // 2. Confirm: true
    fakeCommandPrompts([
        ['scaffold', 'sail'],
        true
    ]);

    // Create a dummy artisan file to simulate a Laravel project
    touch($dir.'/artisan');

    $this->artisan('install', ['path' => $dir])
        ->assertExitCode(0);

    // Verify installers were called
    // ScaffoldInstaller copies files and runs boost:install
    // SailInstaller runs composer require and sail:install
    expect($commands)->toContain('composer require laravel/boost --dev --no-interaction');
    expect($commands)->toContain('php artisan boost:install');
    expect($commands)->toContain('composer require laravel/sail --dev --no-interaction');
    expect($commands)->toContain('php artisan sail:install --with=mysql,redis,meilisearch,mailpit,selenium');

    deleteTempProject($dir);
});

test('install command handles bootstrap mode for new projects', function () {
    $dir = createTempProject(); // empty dir, no artisan
    $commands = [];
    mockProcesses(true, $commands);
    
    // 1. Project name: my-new-app
    // 2. Multiselect: scaffold
    // 3. Confirm: true
    fakeCommandPrompts([
        'my-new-app',
        ['scaffold'],
        true
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
