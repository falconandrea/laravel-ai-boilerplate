<?php

declare(strict_types=1);

namespace App\Installers;

use Illuminate\Support\Facades\File;

use function Laravel\Prompts\info;

/**
 * Scaffolds AI context files and installs Laravel Boost.
 *
 * Copies the .ai/ and .agents/ directory structure into the target project,
 * then installs Laravel Boost which generates the AGENTS.md file.
 */
class ScaffoldInstaller extends BaseInstaller
{
    public function name(): string
    {
        return 'Scaffold AI Context + Boost';
    }

    public function install(): array
    {
        $warnings = [];

        // Step 1: Copy stubs into the target project
        $stubsPath = $this->resolveStubsPath();

        if (! is_dir($stubsPath)) {
            return $this->result(false, ['Stubs directory not found. Is the CLI installed correctly?']);
        }

        $this->copyDirectory($stubsPath, $this->basePath);
        info('✓ AI context files (.ai/, .agents/) scaffolded.');

        // Step 2: Ensure Laravel Boost is required (will be configured at the end of the process)
        if (! $this->alreadyInstalled('laravel/boost')) {
            if (! $this->runComposer('laravel/boost', dev: true)) {
                $warnings[] = 'Failed to install Laravel Boost via Composer.';

                return $this->result(false, $warnings);
            }
        }

        return $this->result(true, $warnings);
    }

    /**
     * Resolve the path to the stubs/scaffold directory.
     * Works both from source and from a compiled .phar.
     */
    private function resolveStubsPath(): string
    {
        // When running from source
        $path = dirname(__DIR__, 2).'/stubs/scaffold';

        if (is_dir($path)) {
            return $path;
        }

        // Fallback for phar
        return \Phar::running(false)
            ? dirname(\Phar::running(false)).'/stubs/scaffold'
            : $path;
    }

    /**
     * Recursively copy a directory, skipping files that already exist in the target.
     */
    private function copyDirectory(string $source, string $destination): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relativePath = substr($item->getPathname(), strlen($source) + 1);
            $targetPath = $destination.'/'.$relativePath;

            if ($item->isDir()) {
                if (! is_dir($targetPath)) {
                    mkdir($targetPath, 0755, true);
                }
            } else {
                // Do not overwrite existing files — idempotent
                if (! file_exists($targetPath)) {
                    $targetDir = dirname($targetPath);
                    if (! is_dir($targetDir)) {
                        mkdir($targetDir, 0755, true);
                    }
                    copy($item->getPathname(), $targetPath);
                }
            }
        }
    }
}
