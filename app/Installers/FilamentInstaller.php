<?php

declare(strict_types=1);

namespace App\Installers;

/**
 * Installs and configures Filament (admin panel).
 */
class FilamentInstaller extends BaseInstaller
{
    public function name(): string
    {
        return 'Filament';
    }

    public function install(): array
    {
        $warnings = [];

        if ($this->alreadyInstalled('filament/filament')) {
            return $this->result(true, ['Filament is already installed.']);
        }

        if (! $this->runComposer('filament/filament:"^3.0"')) {
            return $this->result(false, ['Failed to install Filament via Composer.']);
        }

        if (! $this->runArtisan('filament:install --panels')) {
            $warnings[] = 'filament:install failed. Run it manually.';
        }

        $migration = $this->runMigrations();
        if ($migration['warning']) {
            $warnings[] = $migration['warning'];
        }

        return $this->result(true, $warnings);
    }
}
