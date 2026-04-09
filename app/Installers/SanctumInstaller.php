<?php

declare(strict_types=1);

namespace App\Installers;

/**
 * Installs and configures Laravel Sanctum (API authentication).
 */
class SanctumInstaller extends BaseInstaller
{
    public function name(): string
    {
        return 'Laravel Sanctum';
    }

    public function install(): array
    {
        $warnings = [];

        if ($this->alreadyInstalled('laravel/sanctum')) {
            return $this->result(true, ['Laravel Sanctum is already installed.']);
        }

        if (! $this->runComposer('laravel/sanctum')) {
            return $this->result(false, ['Failed to install Laravel Sanctum via Composer.']);
        }

        if (! $this->runArtisan('vendor:publish --provider="Laravel\\Sanctum\\SanctumServiceProvider"')) {
            $warnings[] = 'vendor:publish for Sanctum failed. Run it manually.';
        }

        $migration = $this->runMigrations();
        if ($migration['warning']) {
            $warnings[] = $migration['warning'];
        }

        return $this->result(true, $warnings);
    }
}
