# 🤖 AI Agent Development Template

A production-ready template for building Laravel 13 projects with AI agents. Combines **persistent project memory** with **Laravel Boost** to give agents robust procedural knowledge and project-specific context — without context drift across sessions.

Compatible with Antigravity, OpenCode, Claude Code, and any agent that reads `AGENTS.md`.

## What is This?

Two problems kill AI-assisted development:

1. **Context drift** — the agent forgets what was built, what went wrong, what decisions were made
2. **Procedural ignorance** — the agent doesn't know your stack's best practices and reinvents them badly every time

This template solves both. The `.ai/` folder handles project memory (state specific to *this* project). Laravel Boost locally ensures agents understand best practices for PHP, Laravel, and your specific implementation.

## Supported Stacks

**Laravel 13** — PHP 8.3, Laravel Sail, MySQL 8 / PostgreSQL

## Quick Start

### 1. Prepare your Laravel project

You can apply this boilerplate to a fresh or existing Laravel project.

**For a new project:**
Run the following command to install Laravel:
```bash
composer create-project laravel/laravel my-new-project
cd my-new-project
```

Then, import the boilerplate files into your project:

```bash
git clone <this-repo-url> temp-ai-boilerplate
cp -r temp-ai-boilerplate/.ai temp-ai-boilerplate/AGENTS.md temp-ai-boilerplate/setup-laravel.sh .
rm -rf temp-ai-boilerplate
```

### 2. Configure project

```bash
bash setup-laravel.sh
```

This interactive CLI tool acts as a project initializer. It will prompt you to select the packages you want to install (e.g., Sail, Telescope, Sanctum, Activitylog, or Database Queues). For each selected component, it automatically runs `composer require`, executes the relevant configuration commands, and injects scheduled tasks directly into your `routes/console.php`. **Laravel Boost** is installed seamlessly by default as it is required for the AI agent context.

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

## License

MIT — use freely for any project.

---

Built to make AI-assisted development actually work across sessions.