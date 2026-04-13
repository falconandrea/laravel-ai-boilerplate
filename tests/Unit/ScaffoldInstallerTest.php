<?php

declare(strict_types=1);

use App\Installers\ScaffoldInstaller;

test('scaffold installer has correct name', function () {

    $installer = new ScaffoldInstaller('/tmp');

    expect($installer->name())->toBe('Scaffold AI Context + Boost');
});

test('stubs directory exists and contains expected files', function () {
    $stubsPath = dirname(__DIR__, 2).'/stubs/scaffold';

    expect(is_dir($stubsPath))->toBeTrue();
    expect(file_exists($stubsPath.'/.ai/memory/progress.md'))->toBeTrue();
    expect(file_exists($stubsPath.'/.ai/memory/lessons.md'))->toBeTrue();
    expect(file_exists($stubsPath.'/.ai/context/TECH_STACK.md'))->toBeTrue();
    expect(file_exists($stubsPath.'/.ai/context/PRD.md'))->toBeTrue();
    expect(file_exists($stubsPath.'/.ai/context/APP_FLOW.md'))->toBeTrue();
    expect(file_exists($stubsPath.'/.ai/features/_TEMPLATE.md'))->toBeTrue();
    expect(file_exists($stubsPath.'/.ai/prompts/project_setup.md'))->toBeTrue();
    expect(file_exists($stubsPath.'/.ai/prompts/create_prd.md'))->toBeTrue();
    expect(file_exists($stubsPath.'/.ai/prompts/generate_tasks.md'))->toBeTrue();
    expect(file_exists($stubsPath.'/.agents/workflows/start.md'))->toBeTrue();
    expect(file_exists($stubsPath.'/.agents/workflows/setup.md'))->toBeTrue();
    expect(file_exists($stubsPath.'/.agents/workflows/feature.md'))->toBeTrue();
});

test('scaffold installer copies files and installs boost', function () {
    $dir = createTempProject();
    $commands = [];
    mockProcesses(true, $commands);

    $installer = new ScaffoldInstaller($dir);
    $result = $installer->install();

    expect($result['success'])->toBeTrue();
    expect(file_exists($dir.'/.ai/memory/progress.md'))->toBeTrue();
    expect(file_exists($dir.'/.agents/workflows/setup.md'))->toBeTrue();

    expect($commands)->toContain('composer require laravel/boost --dev --no-interaction');
    expect($commands)->not->toContain('php artisan boost:install');

    deleteTempProject($dir);
});

test('scaffold installer updates boost if already installed', function () {
    $dir = createTempProject();
    $commands = [];
    mockProcesses(true, $commands);

    // Simulate Boost already installed
    file_put_contents($dir.'/composer.json', json_encode([
        'require-dev' => ['laravel/boost' => '^1.0'],
    ]));

    $installer = new ScaffoldInstaller($dir);
    $result = $installer->install();

    expect($result['success'])->toBeTrue();
    expect($commands)->not->toContain('composer require laravel/boost --dev --no-interaction');

    deleteTempProject($dir);
});
