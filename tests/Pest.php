<?php

declare(strict_types=1);

use LaravelZero\Framework\Testing\TestCase;
use Laravel\Prompts\Prompt;

uses(TestCase::class)->in('Feature');
uses(TestCase::class)->in('Unit');

/**
 * Capture and mock process execution.
 */
function mockProcesses(bool $success = true, array &$capturedCommands = []): void
{
    $runner = function (array $command) use ($success, &$capturedCommands) {
        $capturedCommands[] = implode(' ', $command);
        return $success;
    };

    \App\Installers\BaseInstaller::$processRunner = $runner;
    \App\Commands\InstallCommand::$processRunner = $runner;
}

/**
 * Fake Laravel Prompts input by mocking the BaseInstaller prompt runner.
 */
function fakePrompts(array $answers): void
{
    \App\Installers\BaseInstaller::$promptRunner = function (string $label, array $options, string $default) use (&$answers) {
        return array_shift($answers) ?? $default;
    };
}

/**
 * Reset the process and prompt runners to their default states.
 */
function resetMockProcesses(): void
{
    \App\Installers\BaseInstaller::$processRunner = null;
    \App\Installers\BaseInstaller::$promptRunner = null;
    \App\Commands\InstallCommand::$processRunner = null;
    \App\Commands\InstallCommand::$promptRunner = null;
}

/**
 * Fake InstallCommand prompts by mocking the static prompt runner.
 */
function fakeCommandPrompts(array $answers): void
{
    \App\Commands\InstallCommand::$promptRunner = function (string $type, string $label, mixed $options, mixed $default) use (&$answers) {
        return array_shift($answers) ?? $default;
    };
}

/**
 * Helper to create a temp project environment for installer tests.
 */
function createTempProject(): string
{
    $dir = sys_get_temp_dir().'/installer_test_'.uniqid();
    @mkdir($dir, 0755, true);
    file_put_contents($dir.'/composer.json', json_encode(['require' => [], 'require-dev' => []]));

    return $dir;
}

/**
 * Cleanup helper for temp projects.
 */
function deleteTempProject(string $dir): void
{
    if (! is_dir($dir)) {
        return;
    }
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($dir);
}

/**
 * Basic setup for installer tests.
 */
afterEach(function () {
    resetMockProcesses();
    // Clean up any remaining temp dirs if they match the prefix
    foreach (glob(sys_get_temp_dir().'/installer_test_*') as $dir) {
        if (is_dir($dir)) {
            // Only cleanup if it's older than 1 minute to avoid deleting current test data
            if (time() - filemtime($dir) > 60) {
                // deleteTempProject($dir); // Disabled for safety, let individual tests cleanup
            }
        }
    }
});
