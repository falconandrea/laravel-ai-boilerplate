<?php

declare(strict_types=1);

namespace App\Installers;

/**
 * Installs Laravel Pint (code style fixer).
 */
class PintInstaller extends BaseInstaller
{
    public function name(): string
    {
        return 'Laravel Pint';
    }

    public function install(): array
    {
        if ($this->alreadyInstalled('laravel/pint')) {
            return $this->result(true, ['Laravel Pint is already installed.']);
        }

        if (! $this->runComposer('laravel/pint', dev: true)) {
            return $this->result(false, ['Failed to install Laravel Pint via Composer.']);
        }

        return $this->result(true);
    }
}
