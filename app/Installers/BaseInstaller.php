<?php

declare(strict_types=1);

namespace App\Installers;

use App\Support\FileModifier;
use Symfony\Component\Process\Process;

use function Laravel\Prompts\warning;

/**
 * Abstract base class for all component installers.
 * Provides shared helpers for Composer, Artisan, file modification, and scheduling.
 */
abstract class BaseInstaller
{
    /**
     * The base path of the Laravel project being configured.
     */
    protected string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
    }

    /**
     * Run the installation logic. Each installer must implement this.
     *
     * @return array{success: bool, warnings: list<string>}
     */
    abstract public function install(): array;

    /**
     * Human-readable name of the component.
     */
    abstract public function name(): string;

    /**
     * A callback to intercept and mock prompt selection during tests.
     *
     * @var (callable(string, string, array|string, string|array): string|array)|null
     */
    public static $promptRunner = null;

    /**
     * Helper to wrap Laravel Prompts select() for testability.
     */
    protected function select(string $label, array $options, string $default = ''): string
    {
        if (static::$promptRunner) {
            return (static::$promptRunner)('select', $label, $options, $default);
        }

        return \Laravel\Prompts\select($label, $options, $default);
    }

    /**
     * Helper to wrap Laravel Prompts multiselect() for testability.
     *
     * @param  array<string, string>  $options
     * @param  list<string>  $default
     * @return list<string>
     */
    protected function multiselect(string $label, array $options, array $default = []): array
    {
        if (static::$promptRunner) {
            return (static::$promptRunner)('multiselect', $label, $options, $default);
        }

        return \Laravel\Prompts\multiselect($label, $options, $default);
    }

    /**
     * A callback to intercept and mock process execution during tests.


    /**
     * A callback to intercept and mock process execution during tests.
     *
     * @var (callable(array<string>, string): bool)|null
     */
    public static $processRunner = null;

    /**
     * Run a Composer require command.
     */
    protected function runComposer(string $package, bool $dev = false, ?float $timeout = null): bool
    {
        $command = ['composer', 'require', $package];
        if ($dev) {
            $command[] = '--dev';
        }
        $command[] = '--no-interaction';

        return $this->runProcess($command, $timeout);
    }

    /**
     * Run an Artisan command in the target project.
     */
    protected function runArtisan(string $command, ?float $timeout = null): bool
    {
        $parts = explode(' ', $command);

        return $this->runProcess(array_merge(['php', 'artisan'], $parts), $timeout);
    }

    /**
     * Run migrations with graceful failure handling.
     *
     * @return array{success: bool, warning: string|null}
     */
    protected function runMigrations(?float $timeout = null): array
    {
        $command = ['php', 'artisan', 'migrate', '--no-interaction'];

        if ($this->runProcess($command, $timeout)) {
            return ['success' => true, 'warning' => null];
        }

        return [
            'success' => false,
            'warning' => 'Migration deferred (is database running?). Run `php artisan migrate` manually later.',
        ];
    }

    /**
     * Modify a file in the target project using str_replace.
     */
    protected function modifyFile(string $relativePath, string $search, string $replace): bool
    {
        $filePath = $this->basePath.'/'.$relativePath;

        return FileModifier::replace($filePath, $search, $replace);
    }

    /**
     * Inject a schedule entry into routes/console.php.
     * Creates the file with the Schedule facade if it does not exist.
     */
    protected function injectSchedule(string $code): bool
    {
        $consolePath = $this->basePath.'/routes/console.php';

        // Create console.php if it does not exist
        if (! file_exists($consolePath)) {
            warning('routes/console.php not found. Creating it...');
            $defaultContent = "<?php\n\nuse Illuminate\\Support\\Facades\\Schedule;\n";
            FileModifier::ensureFileExists($consolePath, $defaultContent);
        }

        // Ensure the Schedule facade import exists
        $contents = file_get_contents($consolePath);
        if (! str_contains($contents, 'use Illuminate\\Support\\Facades\\Schedule;')) {
            FileModifier::injectAfter($consolePath, "<?php\n", "\nuse Illuminate\\Support\\Facades\\Schedule;\n");
        }

        // Append the schedule entry
        return FileModifier::appendToFile($consolePath, $code);
    }

    /**
     * Check if a package is already in the target project's composer.json.
     */
    protected function alreadyInstalled(string $package): bool
    {
        $composerPath = $this->basePath.'/composer.json';
        if (! file_exists($composerPath)) {
            return false;
        }

        $composer = json_decode(file_get_contents($composerPath), true);
        $require = $composer['require'] ?? [];
        $requireDev = $composer['require-dev'] ?? [];

        return isset($require[$package]) || isset($requireDev[$package]);
    }

    /**
     * Add a use statement to a file in the target project.
     */
    protected function addUseStatement(string $relativePath, string $fullyQualifiedClass): bool
    {
        $filePath = $this->basePath.'/'.$relativePath;

        return FileModifier::addUseStatement($filePath, $fullyQualifiedClass);
    }

    /**
     * Add a trait to a class in the target project.
     */
    protected function addTrait(string $relativePath, string $traitName): bool
    {
        $filePath = $this->basePath.'/'.$relativePath;

        return FileModifier::addTrait($filePath, $traitName);
    }

    /**
     * Run a process in the target project directory.
     */
    protected function runProcess(array $command, ?float $timeout = null): bool
    {
        if (static::$processRunner) {
            return (static::$processRunner)($command, $this->basePath);
        }

        $process = new Process($command, $this->basePath, null, null, $timeout);
        $process->run();

        return $process->isSuccessful();
    }

    /**
     * Build a result array with success status and optional warnings.
     *
     * @param  list<string>  $warnings
     * @return array{success: bool, warnings: list<string>}
     */
    protected function result(bool $success, array $warnings = []): array
    {
        return ['success' => $success, 'warnings' => $warnings];
    }
}
