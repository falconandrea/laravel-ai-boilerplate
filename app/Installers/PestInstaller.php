<?php

declare(strict_types=1);

namespace App\Installers;

/**
 * Installs Pest (testing framework).
 */
class PestInstaller extends BaseInstaller
{
    public function name(): string
    {
        return 'Pest';
    }

    public function install(): array
    {
        $warnings = [];

        if ($this->alreadyInstalled('pestphp/pest')) {
            return $this->result(true, ['Pest is already installed.']);
        }

        if (! $this->runComposer('pestphp/pest --with-all-dependencies', dev: true)) {
            return $this->result(false, ['Failed to install Pest via Composer.']);
        }

        if (! $this->runArtisan('pest:install')) {
            $warnings[] = 'pest:install failed. Run it manually: php artisan pest:install';
        }

        return $this->result(true, $warnings);
    }
}
