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

        $result = $this->runProcess([
            'composer', 'require', 'pestphp/pest',
            '--dev', '--with-all-dependencies', '--no-interaction',
        ]);

        if (! $result['success']) {
            return $this->result(false, ['Failed to install Pest via Composer.']);
        }

        $init = $this->runProcess(['./vendor/bin/pest', '--init']);
        if (! $init['success']) {
            $warnings[] = 'pest --init failed. Run it manually: ./vendor/bin/pest --init';
        }

        return $this->result(true, $warnings);
    }
}
