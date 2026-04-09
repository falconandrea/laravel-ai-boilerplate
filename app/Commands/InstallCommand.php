<?php

declare(strict_types=1);

namespace App\Commands;

use App\Installers\BaseInstaller;
use App\Installers\BreezeInstaller;
use App\Installers\ExcelInstaller;
use App\Installers\FilamentInstaller;
use App\Installers\LivewireInstaller;
use App\Installers\QueuesInstaller;
use App\Installers\SailInstaller;
use App\Installers\SanctumInstaller;
use App\Installers\ScaffoldInstaller;
use App\Installers\SpatieActivitylogInstaller;
use App\Installers\SpatiePermissionInstaller;
use App\Installers\TelescopeInstaller;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Process\Process;

use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\note;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;
use function Laravel\Prompts\warning;

/**
 * Main interactive command for the Laravel Boilerplate CLI.
 *
 * Supports two modes:
 *  - Bootstrap: creates a new Laravel project from scratch
 *  - Install:   adds components to an existing Laravel project
 */
class InstallCommand extends Command
{
    protected $signature = 'install
        {path? : Path to the Laravel project (defaults to current directory)}
        {--bootstrap : Force bootstrap mode (create a new project)}';

    protected $description = 'Interactively install and configure Laravel components';

    /**
     * Map of component keys to their installer classes.
     *
     * @var array<string, class-string<BaseInstaller>>
     */
    private array $installerMap = [
        'scaffold' => ScaffoldInstaller::class,
        'sail' => SailInstaller::class,
        'telescope' => TelescopeInstaller::class,
        'sanctum' => SanctumInstaller::class,
        'activitylog' => SpatieActivitylogInstaller::class,
        'permission' => SpatiePermissionInstaller::class,
        'livewire' => LivewireInstaller::class,
        'filament' => FilamentInstaller::class,
        'breeze' => BreezeInstaller::class,
        'excel' => ExcelInstaller::class,
        'queues' => QueuesInstaller::class,
    ];

    /**
     * Human-readable labels for the multiselect.
     *
     * @var array<string, string>
     */
    private array $labels = [
        'scaffold' => '⭐ Scaffold AI Context + Laravel Boost',
        'sail' => 'Laravel Sail (Docker dev environment)',
        'telescope' => 'Laravel Telescope (Debug assistant)',
        'sanctum' => 'Laravel Sanctum (API authentication)',
        'activitylog' => 'Spatie Activitylog (User activity logging)',
        'permission' => 'Spatie Permission (Roles & permissions)',
        'livewire' => 'Livewire (Reactive components)',
        'filament' => 'Filament (Admin panel)',
        'breeze' => 'Laravel Breeze (Starter kit with auth)',
        'excel' => 'Maatwebsite Excel (Import/Export)',
        'queues' => 'Database Queues (Schedule setup)',
    ];

    /**
     * A callback to intercept and mock process execution during tests.
     *
     * @var (callable(array<string>, string|null): bool)|null
     */
    public static $processRunner = null;

    /**
     * A callback to intercept and mock prompt selection during tests.
     *
     * @var (callable(string, string, mixed, mixed): mixed)|null
     */
    public static $promptRunner = null;

    protected function executeShellCommand(array $command, ?string $cwd = null, int $timeout = 60): bool
    {
        if (static::$processRunner) {
            return (static::$processRunner)($command, $cwd);
        }

        $process = new Process($command, $cwd, null, null, $timeout);
        $process->run();

        return $process->isSuccessful();
    }

    protected function promptSelection(string $type, string $label, mixed $options = null, mixed $default = null): mixed
    {
        if (static::$promptRunner) {
            return (static::$promptRunner)($type, $label, $options, $default);
        }

        return match ($type) {
            'multiselect' => \Laravel\Prompts\multiselect($label, $options),
            'confirm' => \Laravel\Prompts\confirm($label, $default ?? true),
            'text' => \Laravel\Prompts\text($label, $options ?? '', required: true),
            'select' => \Laravel\Prompts\select($label, $options, $default),
            default => throw new \InvalidArgumentException("Unknown prompt type: {$type}"),
        };
    }

    public function handle(): int
    {
        $this->displayBanner();

        $path = $this->argument('path') ?? getcwd();
        $path = realpath($path) ?: $path;

        // --- Step 1: Detect mode ---
        $isBootstrap = $this->option('bootstrap') || ! $this->isLaravelProject($path);

        if ($isBootstrap) {
            $path = $this->bootstrapNewProject($path);
            if ($path === null) {
                return self::FAILURE;
            }
        } else {
            info("✓ Detected existing Laravel project at: {$path}");
        }

        // --- Step 2: Select components ---
        $selected = $this->promptSelection(
            type: 'multiselect',
            label: 'Which components do you want to install?',
            options: $this->labels
        );

        if (empty($selected)) {
            warning('No components selected. Nothing to do.');

            return self::SUCCESS;
        }

        // --- Step 3: Confirmation ---
        $this->displaySummary($selected);

        if (! $this->promptSelection('confirm', 'Proceed with installation?')) {
            warning('Installation cancelled.');

            return self::SUCCESS;
        }

        // --- Step 4: Run installers ---
        $results = $this->runInstallers($selected, $path);

        // --- Step 5: Final report ---
        $this->displayResults($results, $path);

        return self::SUCCESS;
    }

    /**
     * Print the CLI banner.
     */
    private function displayBanner(): void
    {
        $this->newLine();
        $this->line('<fg=cyan;options=bold>  ╔══════════════════════════════════════════╗</>');
        $this->line('<fg=cyan;options=bold>  ║    Laravel Boilerplate CLI               ║</>');
        $this->line('<fg=cyan;options=bold>  ║    Interactive project configurator      ║</>');
        $this->line('<fg=cyan;options=bold>  ╚══════════════════════════════════════════╝</>');
        $this->newLine();
    }

    /**
     * Check if the path is a Laravel project.
     */
    private function isLaravelProject(string $path): bool
    {
        return file_exists($path.'/artisan');
    }

    /**
     * Create a new Laravel project using composer.
     */
    private function bootstrapNewProject(string $basePath): ?string
    {
        $projectName = $this->promptSelection(
            type: 'text',
            label: 'What is your project name?',
            options: 'my-laravel-app' // used as placeholder in the helper
        );

        $projectPath = rtrim($basePath, '/').'/'.$projectName;

        $created = spin(
            callback: fn () => $this->executeShellCommand(
                ['composer', 'create-project', '--prefer-dist', 'laravel/laravel', $projectPath],
                timeout: 300
            ),
            message: 'Creating fresh Laravel project...',
        );

        if (! $created) {
            $this->error("Failed to create Laravel project at {$projectPath}.");

            return null;
        }

        return $projectPath;
    }

    /**
     * Display a summary of selected components before installation.
     *
     * @param  list<string>  $selected
     */
    private function displaySummary(array $selected): void
    {
        $this->newLine();
        note('The following components will be installed:');

        foreach ($selected as $key) {
            $this->line("  <fg=green>•</> {$this->labels[$key]}");
        }

        $this->newLine();
    }

    /**
     * Run all selected installers.
     */
    private function runInstallers(array $selected, string $path): array
    {
        $results = [];

        foreach ($selected as $key) {
            $installerClass = $this->installerMap[$key];
            /** @var BaseInstaller $installer */
            $installer = new $installerClass($path);

            $result = spin(
                callback: fn () => $installer->install(),
                message: "Installing {$installer->name()}...",
            );

            $results[] = [
                'name' => $installer->name(),
                'success' => $result['success'],
                'warnings' => $result['warnings'],
            ];

            if ($result['success']) {
                info("✓ {$installer->name()} installed successfully.");
            } else {
                $this->error("✗ {$installer->name()} installation failed.");
            }

            foreach ($result['warnings'] as $w) {
                warning("  ⚠ {$w}");
            }
        }

        return $results;
    }

    /**
     * Display the final results table.
     *
     * @param  list<array{name: string, success: bool, warnings: list<string>}>  $results
     */
    private function displayResults(array $results, string $path): void
    {
        $this->newLine();
        note('Installation Summary');

        $rows = [];
        foreach ($results as $r) {
            $status = $r['success'] ? '<fg=green>✓ Success</>' : '<fg=red>✗ Failed</>';
            $warns = count($r['warnings']) > 0 ? implode('; ', $r['warnings']) : '—';
            $rows[] = [$r['name'], $status, $warns];
        }

        table(
            headers: ['Component', 'Status', 'Notes'],
            rows: $rows,
        );

        $succeeded = count(array_filter($results, fn ($r) => $r['success']));
        $failed = count($results) - $succeeded;

        $this->newLine();
        info("Done! {$succeeded} component(s) installed successfully.");

        if ($failed > 0) {
            warning("{$failed} component(s) had issues. Review the notes above.");
        }

        // --- Step 6: Custom instructions for Laravel Boost ---
        $composerPath = $path.'/composer.json';
        if (file_exists($composerPath)) {
            $composer = json_decode(file_get_contents($composerPath), true);
            $allDeps = array_merge($composer['require'] ?? [], $composer['require-dev'] ?? []);

            if (isset($allDeps['laravel/boost'])) {
                $projectName = basename($path);
                $this->newLine();
                note('NEXT STEPS: Finish Laravel Boost configuration');
                $this->line("  1. Run: <fg=cyan>cd {$projectName}</>");
                $this->line('  2. Run: <fg=cyan>php artisan boost:install</>');
                $this->newLine();
            }
        }
    }
}
