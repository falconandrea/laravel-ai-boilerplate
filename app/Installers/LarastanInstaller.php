<?php

declare(strict_types=1);

namespace App\Installers;

use App\Support\FileModifier;

/**
 * Installs Larastan (static analysis for Laravel).
 */
class LarastanInstaller extends BaseInstaller
{
    public function name(): string
    {
        return 'Larastan';
    }

    public function install(): array
    {
        $warnings = [];

        if ($this->alreadyInstalled('larastan/larastan')) {
            return $this->result(true, ['Larastan is already installed.']);
        }

        if (! $this->runComposer('larastan/larastan', dev: true)) {
            return $this->result(false, ['Failed to install Larastan via Composer.']);
        }

        // Create a default phpstan.neon.dist if not present
        $neonPath = $this->basePath.'/phpstan.neon.dist';
        if (! file_exists($neonPath)) {
            $content = <<<'NEON'
includes:
    - vendor/larastan/larastan/extension.neon

parameters:
    paths:
        - app/

    level: 5
NEON;
            FileModifier::ensureFileExists($neonPath, $content);
        } else {
            $warnings[] = 'phpstan.neon.dist already exists, skipped creation.';
        }

        return $this->result(true, $warnings);
    }
}
