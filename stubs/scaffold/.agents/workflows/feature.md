---
description: New feature workflow - create a PRD, get approval, generate tasks
---

## Steps

1. Activate **PLANNING MODE**. No code will be written during this phase.

2. Read existing `.ai/context/` files (like `TECH_STACK.md`, `PRD.md`) to understand constraints.

3. Ask the user to describe the feature. Ask clarifying questions as needed to gather full requirements.

4. Write the PRD following the template in `.ai/features/_TEMPLATE.md` and save it to `.ai/features/[feature-name]/prd-[feature-name].md`.

5. Present the PRD to the user and ask for approval.

6. Once the PRD is approved, generate the task list and save it to `.ai/features/[feature-name]/tasks-[feature-name].md`.
   Ensure any database schema changes are explicitly planned in the tasks (including updating `.ai/context/database_schema.mmd`).
   Update `.ai/memory/progress.md` with the new feature.

7. Exit plan mode and ask the user if you should start the implementation (ACTING MODE).
