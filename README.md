# 🤖 AI Agent Development Template

A production-ready template for building Laravel 13 projects with AI agents. Combines **persistent project memory** with **Laravel Boost** to give agents robust procedural knowledge and project-specific context — without context drift across sessions.

Compatible with Antigravity, OpenCode, Claude Code, and any agent that reads `AGENTS.md`.

## What is This?

Two problems kill AI-assisted development:

1. **Context drift** — the agent forgets what was built, what went wrong, what decisions were made
2. **Procedural ignorance** — the agent doesn't know your stack's best practices and reinvents them badly every time

This template solves both. The `.ai/` folder handles project memory (state specific to *this* project). Laravel Boost locally ensures agents understand best practices for PHP, Laravel, and your specific implementation.

## Quick Start

### 1. Clone and initialize

```bash
git clone <this-repo> my-new-project
cd my-new-project
rm -rf .git && git init
```

### 2. Configure project

```bash
bash setup-laravel.sh
```

Follow the interactive prompts to install standard packages like Sail, Telescope, Sanctum, Activitylog, or set up queue tables automatically. **Laravel Boost** is installed by default as it is required for the AI agent context.

### 3. Run project setup

**Antigravity / OpenCode:**
```
/setup
```

**Other agents:** paste `.ai/prompts/project_setup.md` in chat and answer the questions.

### 4. Start building

```
/start            → restores full session context
/feature [desc]   → plan mode: PRD + task list
```

---

## Repository Structure

```
.
├── AGENTS.md                 # Always-on rules (no useEffect, TS strict, naming...)

│
└── .ai/
    ├── context/              # Project-specific docs (filled during /setup)
    │   ├── TECH_STACK.md
    │   ├── PRD.md
    │   ├── APP_FLOW.md
    │   └── database_schema.mmd
    │
    ├── memory/               # Persistent AI memory — never delete
    │   ├── progress.md
    │   ├── lessons.md
    │   └── blockers.md
    │
    ├── features/             # One folder per feature
    │   ├── _TEMPLATE.md
    │   └── [feature-name]/
    │       ├── prd-*.md
    │       └── tasks-*.md
    │
    ├── prompts/              # For agents without slash commands
    │   ├── project_setup.md
    │   ├── create_prd.md
    │   ├── generate_tasks.md
    │   ├── refactoring.md
    │   └── deployment.md
    │
    ├── guidelines/           # Reserved for Laravel Boost auto-generated files
    │
    │
    └── workflows/
        ├── start.md          # /start
        ├── setup.md          # /setup
        └── feature.md        # /feature
```

---

## How It Works

### Two layers, two jobs

| Layer | What it contains | Who maintains it |
|---|---|---|
| `AGENTS.md` + Boost | Procedural knowledge — how to write good code | Community + you |
| `.ai/memory/` + `.ai/context/` | Project state — what was built, decided, broken | You + the agent |

### Session flow

1. Agent reads `AGENTS.md` (always-on rules)
2. `/start` → reads `memory/progress.md`, `memory/lessons.md`, `memory/blockers.md`
3. Agent picks up exactly where you left off

### Feature flow

1. `/feature [description]` → plan mode
2. Clarifying questions → PRD → task list
3. Exit plan mode → implement

---

## Slash Commands

| Command | File | What it does |
|---|---|---|
| `/start` | `workflows/start.md` | Reads memory, summarises state |
| `/setup` | `workflows/setup.md` | Full discovery → generates context docs |
| `/feature` | `workflows/feature.md` | Plan mode → PRD → task list |

---

## Laravel Boost

Laravel projects use **Laravel Boost** for context generation instead of a TECH_STACK template:

```bash
composer require laravel/boost --dev
php artisan boost:install
```

Boost writes to `.ai/guidelines/` (separate from your files) and generates `.mcp.json` for MCP tool access. It does not touch `AGENTS.md` or `.ai/memory/`. The `TECH_STACK.md` for Laravel projects is filled during `/setup` with project-specific decisions (database, auth, deployment, etc.).

---

## Supported Stacks

**Laravel 13** — PHP 8.3, Laravel Sail, MySQL 8 / PostgreSQL

---

## License

MIT — use freely for any project.

---

Built to make AI-assisted development actually work across sessions.