<?php

declare(strict_types=1);

use App\Commands\InstallCommand;
use App\Installers\BaseInstaller;
use Laravel\Prompts\Prompt;
use LaravelZero\Framework\Testing\TestCase;

uses(TestCase::class)->in('Feature');
uses(TestCase::class)->in('Unit');

/**
 * Capture and mock process execution.
 */
function mockProcesses(bool $success = true, array &$capturedCommands = []): void
{
    $runner = function (array $command, ?string $cwd = null) use ($success, &$capturedCommands) {
        $cmdStr = implode(' ', $command);
        $capturedCommands[] = $cmdStr;

        // Side effect: if 'composer require', simulate updating composer.json
        if ($success && str_contains($cmdStr, 'composer require') && $cwd) {
            $composerPath = $cwd.'/composer.json';
            if (file_exists($composerPath)) {
                $composer = json_decode(file_get_contents($composerPath), true);
                $package = null;
                foreach ($command as $part) {
                    if (! in_array($part, ['composer', 'require']) && ! str_starts_with($part, '-')) {
                        $package = $part;
                        break;
                    }
                }
                if ($package) {
                    $key = str_contains($cmdStr, '--dev') ? 'require-dev' : 'require';
                    $composer[$key][$package] = '^1.0';
                    file_put_contents($composerPath, json_encode($composer));
                }
            }
        }

        // Side effect: if 'composer create-project', simulate artisan file and composer.json
        if ($success && str_contains($cmdStr, 'create-project') && ! str_contains($cmdStr, '--version')) {
            $projectPath = end($command);
            @mkdir($projectPath, 0755, true);
            touch($projectPath.'/artisan');
            file_put_contents($projectPath.'/composer.json', json_encode(['require' => [], 'require-dev' => []]));
        }

        return $success;
    };

    BaseInstaller::$processRunner = $runner;
    InstallCommand::$processRunner = $runner;
}

/**
 * Fake Laravel Prompts input by mocking the BaseInstaller prompt runner.
 */
function fakePrompts(array $answers): void
{
    BaseInstaller::$promptRunner = function (string $type, string $label, array $options, mixed $default) use (&$answers) {
        return array_shift($answers) ?? $default;
    };
}

/**
 * Reset the process and prompt runners to their default states.
 */
function resetMockProcesses(): void
{
    BaseInstaller::$processRunner = null;
    BaseInstaller::$promptRunner = null;
    InstallCommand::$processRunner = null;
    InstallCommand::$promptRunner = null;
}

/**
 * Fake InstallCommand prompts by mocking the static prompt runner.
 */
function fakeCommandPrompts(array $answers): void
{
    InstallCommand::$promptRunner = function (string $type, string $label, mixed $options, mixed $default) use (&$answers) {
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
