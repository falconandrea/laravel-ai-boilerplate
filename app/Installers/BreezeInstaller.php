<?php

declare(strict_types=1);

namespace App\Installers;

use function Laravel\Prompts\select;

/**
 * Installs and configures Laravel Breeze (starter kit).
 * Asks the user which stack to use: Blade, Livewire, or React.
 */
class BreezeInstaller extends BaseInstaller
{
    public function name(): string
    {
        return 'Laravel Breeze';
    }

    public function install(): array
    {
        $warnings = [];

        if ($this->alreadyInstalled('laravel/breeze')) {
            return $this->result(true, ['Laravel Breeze is already installed.']);
        }

        $stack = $this->select(
            label: 'Which Breeze stack do you want?',
            options: [
                'blade' => 'Blade',
                'livewire' => 'Livewire (requires Livewire)',
                'react' => 'React with Inertia',
            ],
            default: 'blade',
        );

        if (! $this->runComposer('laravel/breeze', dev: true)) {
            return $this->result(false, ['Failed to install Laravel Breeze via Composer.']);
        }

        if (! $this->runArtisan("breeze:install {$stack}")) {
            $warnings[] = "breeze:install {$stack} failed. Run it manually.";
        }

        $migration = $this->runMigrations();
        if ($migration['warning']) {
            $warnings[] = $migration['warning'];
        }

        return $this->result(true, $warnings);
    }
}
