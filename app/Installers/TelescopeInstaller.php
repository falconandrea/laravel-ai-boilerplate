<?php

declare(strict_types=1);

namespace App\Installers;

/**
 * Installs and configures Laravel Telescope (debug assistant).
 */
class TelescopeInstaller extends BaseInstaller
{
    public function name(): string
    {
        return 'Laravel Telescope';
    }

    public function install(): array
    {
        $warnings = [];

        if ($this->alreadyInstalled('laravel/telescope')) {
            return $this->result(true, ['Laravel Telescope is already installed.']);
        }

        if (! $this->runComposer('laravel/telescope', dev: true)) {
            return $this->result(false, ['Failed to install Laravel Telescope via Composer.']);
        }

        if (! $this->runArtisan('telescope:install')) {
            $warnings[] = 'telescope:install failed. Run it manually.';
        }

        $migration = $this->runMigrations();
        if ($migration['warning']) {
            $warnings[] = $migration['warning'];
        }

        $this->injectSchedule("Schedule::command('telescope:prune --hours=48')->daily();");

        return $this->result(true, $warnings);
    }
}
