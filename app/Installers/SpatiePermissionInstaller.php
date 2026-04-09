<?php

declare(strict_types=1);

namespace App\Installers;

/**
 * Installs and configures Spatie Permission (roles & permissions).
 * Adds HasRoles trait to the User model.
 */
class SpatiePermissionInstaller extends BaseInstaller
{
    public function name(): string
    {
        return 'Spatie Permission';
    }

    public function install(): array
    {
        $warnings = [];

        if ($this->alreadyInstalled('spatie/laravel-permission')) {
            return $this->result(true, ['Spatie Permission is already installed.']);
        }

        if (! $this->runComposer('spatie/laravel-permission')) {
            return $this->result(false, ['Failed to install Spatie Permission via Composer.']);
        }

        if (! $this->runArtisan([
            'vendor:publish',
            '--provider=Spatie\Permission\PermissionServiceProvider',
            '--tag=permission-migrations',
        ])) {
            $warnings[] = 'vendor:publish for Permission failed. Run it manually.';
        }

        $migration = $this->runMigrations();
        if ($migration['warning']) {
            $warnings[] = $migration['warning'];
        }

        // Add HasRoles trait to User model
        $userModelPath = $this->basePath.'/app/Models/User.php';
        if (file_exists($userModelPath)) {
            $this->addUseStatement('app/Models/User.php', 'Spatie\\Permission\\Traits\\HasRoles');
            $this->addTrait('app/Models/User.php', 'HasRoles');
        } else {
            $warnings[] = 'User model not found at app/Models/User.php. Add the HasRoles trait manually.';
        }

        return $this->result(true, $warnings);
    }
}
