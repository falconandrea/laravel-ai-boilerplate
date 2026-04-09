<?php

declare(strict_types=1);

namespace App\Installers;

/**
 * Installs and configures Laravel Sail (Docker environment).
 */
class SailInstaller extends BaseInstaller
{
    public function name(): string
    {
        return 'Laravel Sail';
    }

    public function install(): array
    {
        $warnings = [];

        if ($this->alreadyInstalled('laravel/sail')) {
            return $this->result(true, ['Laravel Sail is already installed.']);
        }

        if (! $this->runComposer('laravel/sail', dev: true)) {
            return $this->result(false, ['Failed to install Laravel Sail via Composer.']);
        }

        if (! $this->runArtisan('sail:install --with=mysql,redis,meilisearch,mailpit,selenium')) {
            $warnings[] = 'sail:install failed. You may need to run it manually.';
        }

        return $this->result(true, $warnings);
    }
}
