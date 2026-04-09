<?php

declare(strict_types=1);

namespace App\Installers;

/**
 * Installs and configures Maatwebsite Excel.
 */
class ExcelInstaller extends BaseInstaller
{
    public function name(): string
    {
        return 'Maatwebsite Excel';
    }

    public function install(): array
    {
        $warnings = [];

        if ($this->alreadyInstalled('maatwebsite/excel')) {
            return $this->result(true, ['Maatwebsite Excel is already installed.']);
        }

        if (! $this->runComposer('maatwebsite/excel')) {
            return $this->result(false, ['Failed to install Maatwebsite Excel via Composer.']);
        }

        if (! $this->runArtisan([
            'vendor:publish',
            '--provider=Maatwebsite\Excel\ExcelServiceProvider',
            '--tag=config',
        ])) {
            $warnings[] = 'vendor:publish for Excel failed. Run it manually.';
        }

        return $this->result(true, $warnings);
    }
}
