# AI Agent Operating System — Laravel Boilerplate CLI

> **READ THIS FIRST**: This file tells AI how to work with this CLI project.

## Code Rules

- Strict PHP typing: always use `declare(strict_types=1);`
- All `.ai/` markdown files and code comments MUST be in English
- Follow PSR-4 autoloading under `App\` namespace
- Use Laravel Zero conventions

---

## 🎯 Core Directives

### Session Start Protocol
1. Read `.ai/memory/progress.md` — current state
2. Read `.ai/memory/lessons.md` — past mistakes
3. Read `.ai/context/TECH_STACK.md` — CLI stack (Laravel Zero)
4. Ask: "Ready to continue?"

### Architecture

- **Entry point**: `boilerplate` (PHP CLI script)
- **Main command**: `install` (`App\Commands\InstallCommand`)
- **Installers**: `App\Installers\*` — each extends `BaseInstaller`
- **Interactive Prompts**: Use `multiselect()`, `confirm()`, or `text()` helpers in `BaseInstaller` for UI (ensures testability)
- **File ops**: `App\Support\FileModifier` — str_replace-based, no regex
- **Stubs**: `stubs/scaffold/` — template files copied into target projects

### Adding a New Installer

1. Create `app/Installers/YourInstaller.php` extending `BaseInstaller`
2. Implement `name()` and `install()`
3. Register it in `InstallCommand::$installerMap` and `$labels`

---

## 🚨 Red Flags — Stop and Ask
- Making breaking changes to existing installers
- Modifying `BaseInstaller` public API
- Adding dependencies not needed for a CLI tool

---

## 🔄 Update Loop
After every task:
- Update `.ai/memory/progress.md` with what was completed
- If mistake made, update `.ai/memory/lessons.md`

---

## ⚠️ Critical Rules

1. **NO assumptions** — ask before acting
2. **ALWAYS** check TECH_STACK.md for versions
3. **ALWAYS** update progress.md after completing work
4. **NEVER** skip error handling
5. **ALL code comments MUST be in English**