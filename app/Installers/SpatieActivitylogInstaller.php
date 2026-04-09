<?php

declare(strict_types=1);

namespace App\Installers;

/**
 * Installs and configures Spatie Activitylog.
 */
class SpatieActivitylogInstaller extends BaseInstaller
{
    public function name(): string
    {
        return 'Spatie Activitylog';
    }

    public function install(): array
    {
        $warnings = [];

        if ($this->alreadyInstalled('spatie/laravel-activitylog')) {
            return $this->result(true, ['Spatie Activitylog is already installed.']);
        }

        if (! $this->runComposer('spatie/laravel-activitylog')) {
            return $this->result(false, ['Failed to install Spatie Activitylog via Composer.']);
        }

        if (! $this->runArtisan([
            'vendor:publish',
            '--provider=Spatie\Activitylog\ActivitylogServiceProvider',
            '--tag=activitylog-migrations',
        ])) {
            $warnings[] = 'vendor:publish for Activitylog failed. Run it manually.';
        }

        $migration = $this->runMigrations();
        if ($migration['warning']) {
            $warnings[] = $migration['warning'];
        }

        $this->injectSchedule("Schedule::command('activitylog:clean --force')->daily();");

        return $this->result(true, $warnings);
    }
}
