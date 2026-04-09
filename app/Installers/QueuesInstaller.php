<?php

declare(strict_types=1);

namespace App\Installers;

/**
 * Configures Database Queues.
 * Laravel 11+ ships with queue tables by default, so this only sets up scheduling.
 */
class QueuesInstaller extends BaseInstaller
{
    public function name(): string
    {
        return 'Database Queues';
    }

    public function install(): array
    {
        $warnings = [];

        $this->injectSchedule("Schedule::command('queue:prune-failed --hours=360')->daily();");

        return $this->result(true, $warnings);
    }
}
