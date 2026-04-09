# 🤖 Laravel Boilerplate CLI

A **Laravel Zero** CLI application to interactively bootstrap and configure Laravel 13 projects. Cross-platform, modular, and distributable as a single `.phar` file.

---

## ✨ Features

- **Two modes**: Bootstrap a new Laravel project, or add components to an existing one.
- **Interactive**: Uses [Laravel Prompts](https://laravel.com/docs/prompts) for rich terminal UI (multiselect, confirm, spinners).
- **Modular**: Each package installer is a self-contained PHP class — easy to add new ones.
- **Idempotent**: Safe to re-run; won't duplicate changes already applied.
- **Distributable**: Compile to a single `.phar` file.

---

## 📦 Available Components

| Component | Description |
|---|---|
| ⭐ **Scaffold AI Context + Boost** | Copies `.ai/` and `.agents/` templates + installs Laravel Boost |
| **Laravel Sail** | Docker development environment |
| **Laravel Telescope** | Debug assistant (dev) |
| **Laravel Sanctum** | API token authentication |
| **Spatie Activitylog** | Model activity logging |
| **Spatie Permission** | Roles & permissions (adds `HasRoles` to User model) |
| **Livewire** | Reactive Blade components |
| **Filament** | Admin panel |
| **Laravel Breeze** | Starter kit (Blade / Livewire / React) |
| **Maatwebsite Excel** | Import & export spreadsheets |
| **Database Queues** | Schedule setup for queue pruning |

---

## 🛠 Prerequisites

| Requirement | Version |
|---|---|
| PHP | `>= 8.2` |
| Composer | Latest |
| Git | Latest |
| Docker | _(only if using Sail)_ |

---

## 🚀 Quick Start

### 1. Clone the CLI

```bash
git clone https://github.com/falconandrea/laravel-ai-boilerplate.git boilerplate-cli
cd boilerplate-cli
composer install
```

### 2. Run the CLI

#### Install mode (inside an existing Laravel project)

```bash
cd /path/to/your-laravel-project
php /path/to/boilerplate-cli/boilerplate install
```

#### Bootstrap mode (create a new project from scratch)

```bash
php /path/to/boilerplate-cli/boilerplate install --bootstrap
```

Or simply run from a directory that isn't a Laravel project — the CLI auto-detects and enters bootstrap mode.

### 3. Follow the prompts

The CLI will:
1. Detect whether you are in an existing project or need to bootstrap one.
2. Show a **multiselect** to pick components.
3. Confirm your selection.
4. Install everything with progress spinners.
5. Print a summary report.

---

## ⭐ Scaffold AI Context + Laravel Boost

This is the recommended first component to install. It does two things:

### 1. Copies AI context templates into your project

The scaffold creates a complete `.ai/` and `.agents/` directory structure:

```
your-project/
├── .ai/
│   ├── context/          ← Project docs (TECH_STACK, PRD, APP_FLOW)
│   ├── memory/           ← AI memory (progress, lessons, blockers)
│   ├── features/         ← Feature PRDs and task lists
│   │   └── _TEMPLATE.md
│   └── prompts/          ← Ready-to-use AI prompts
│       ├── project_setup.md
│       ├── create_prd.md
│       └── generate_tasks.md
│
├── .agents/
│   └── workflows/        ← Slash commands (/start, /setup, /feature)
│       ├── start.md
│       ├── setup.md
│       └── feature.md
```

These files give any AI agent (Antigravity, OpenCode, Claude Code) persistent memory across sessions:
- **`/start`** → reads memory, restores context
- **`/setup`** → guided project discovery → generates context docs
- **`/feature`** → plan mode → PRD → task list

### 2. Installs Laravel Boost

[Laravel Boost](https://github.com/laravel/boost) creates an `AGENTS.md` file with procedural knowledge of your Laravel project, giving AI agents deep understanding of your codebase.

### 3. Manual Boost Setup (Optional but Recommended)

After all selected components are installed, the CLI will check if you included the Scaffold component. If so, it will provide a **Next Steps** section with the exact commands needed to finalize the [Laravel Boost](https://github.com/laravel/boost) installation:

```bash
cd your-project-name
php artisan boost:install
```

This ensures that Boost auto-discovers all newly installed packages and gives you full control over the interactive configuration of your preferred AI agents.

> **Note**: Files that already exist in your project won't be overwritten — it's safe to re-run the CLI at any time.

---

## 🧪 Running Tests

```bash
php boilerplate test
```

With coverage report:

```bash
php boilerplate test --coverage
```

---

## 🏗 Building a Phar

Compile the CLI into a single distributable file:

```bash
php boilerplate app:build boilerplate-cli
```

The built phar will be available at `./builds/boilerplate-cli`.

---

## 📁 Project Structure

```
.
├── app/
│   ├── Commands/
│   │   └── InstallCommand.php          ← Main interactive command
│   ├── Installers/
│   │   ├── BaseInstaller.php           ← Abstract with shared helpers
│   │   ├── ScaffoldInstaller.php       ← AI Context + Boost
│   │   ├── SailInstaller.php
│   │   ├── TelescopeInstaller.php
│   │   ├── SanctumInstaller.php
│   │   ├── SpatieActivitylogInstaller.php
│   │   ├── SpatiePermissionInstaller.php
│   │   ├── LivewireInstaller.php
│   │   ├── FilamentInstaller.php
│   │   ├── BreezeInstaller.php
│   │   ├── ExcelInstaller.php
│   │   └── QueuesInstaller.php
│   ├── Providers/
│   │   └── AppServiceProvider.php
│   └── Support/
│       └── FileModifier.php            ← Safe str_replace file ops
│
├── stubs/
│   └── scaffold/                       ← Templates copied to target projects
│       ├── .ai/
│       └── .agents/
│
├── bootstrap/
│   └── app.php
├── config/
│   ├── app.php
│   └── commands.php
│
├── AGENTS.md                           ← Rules for AI working on THIS CLI
├── boilerplate                         ← CLI entry point
└── composer.json
```

---

## 🧩 Adding a New Installer

1. Create `app/Installers/YourInstaller.php` extending `BaseInstaller`.
2. Implement `name()` and `install()`.
3. Register it in `InstallCommand::$installerMap` and `$labels`.

```php
<?php

declare(strict_types=1);

namespace App\Installers;

class YourInstaller extends BaseInstaller
{
    public function name(): string
    {
        return 'Your Package';
    }

    public function install(): array
    {
        $warnings = [];

        if ($this->alreadyInstalled('vendor/package')) {
            return $this->result(true, ['Already installed.']);
        }

        if (! $this->runComposer('vendor/package')) {
            return $this->result(false, ['Composer install failed.']);
        }

        return $this->result(true, $warnings);
    }
}
```

---

## 📄 License

MIT — use freely for any project.

---

Built with [Laravel Zero](https://laravel-zero.com) to make Laravel project setup fast and reproducible.