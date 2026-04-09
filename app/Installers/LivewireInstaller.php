<?php

declare(strict_types=1);

namespace App\Installers;

/**
 * Installs and configures Livewire.
 */
class LivewireInstaller extends BaseInstaller
{
    public function name(): string
    {
        return 'Livewire';
    }

    public function install(): array
    {
        $warnings = [];

        if ($this->alreadyInstalled('livewire/livewire')) {
            return $this->result(true, ['Livewire is already installed.']);
        }

        if (! $this->runComposer('livewire/livewire')) {
            return $this->result(false, ['Failed to install Livewire via Composer.']);
        }

        return $this->result(true, $warnings);
    }
}
