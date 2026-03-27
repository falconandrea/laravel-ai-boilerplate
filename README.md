# 🤖 Laravel AI Agent Boilerplate

A production-ready template for building Laravel 13 projects with AI agents. Combines **persistent project memory** with **Laravel Boost** to give agents robust procedural knowledge and project-specific context — without context drift across sessions.

Compatible with Antigravity, OpenCode, Claude Code, and any agent that reads `AGENTS.md`.

## What is This?

Two problems kill AI-assisted development:

1. **Context drift** — the agent forgets what was built, what went wrong, what decisions were made
2. **Procedural ignorance** — the agent doesn't know your stack's best practices and reinvents them badly every time

This template solves both. The `.ai/` folder handles project memory (state specific to *this* project). Laravel Boost locally ensures agents understand best practices for PHP, Laravel, and your specific implementation.

## Supported Stacks

**Laravel 13** — PHP 8.3+, Laravel Sail, MySQL 8 / PostgreSQL

---

## 🛠 Prerequisites

Ensure you have the following installed on your machine:

- **PHP 8.3+**
- **Composer**
- **Git**
- **Docker** (if using Laravel Sail)

---

## 🚀 Quick Start

### 1. Prepare your Laravel project

You can apply this boilerplate to a **fresh** or **existing** Laravel project.

#### A. New Project
Run the followng to create a fresh Laravel application:
```bash
composer create-project laravel/laravel my-app
cd my-app
```

#### B. Existing Project
Simply navigate to your project root:
```bash
cd your-existing-project
```

#### C. Inject Boilerplate
Copy the AI memory files and setup scripts into your project:
```bash
git clone https://github.com/falconandrea/laravel-ai-boilerplate temp-ai
cp -r temp-ai/.ai temp-ai/AGENTS.md temp-ai/setup-laravel.sh .
rm -rf temp-ai
```

### 2. Technical Configuration
Run the interactive CLI to install and configure Laravel packages (Sail, Telescope, Sanctum, etc.):
```bash
bash setup-laravel.sh
```
> [!NOTE]
> This script handles the **technical stack**. It runs `composer require` and configures your Laravel environment.

### 3. AI Context Discovery
Initialize the AI agent's understanding of your specific project requirements:

**Antigravity / OpenCode:**
```
/setup
```

**Other agents:** 
Paste the contents of `.ai/prompts/project_setup.md` into your chat.

> [!TIP]
> This step is where the AI asks you about your **product vision, features, and architecture** to populate `.ai/context/`.

### 4. Start Building
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