# Lessons Learned

> **PURPOSE**: Document mistakes, bugs, and their solutions so AI never repeats them.

> **CRITICAL**: AI must read this file at session start and check it before making changes.

---

## Format for Each Entry

```markdown
### [DATE] - [CATEGORY] - [Short Title]
**What went wrong**: [Description of the problem]
**Root cause**: [Why it happened]
**Impact**: [What broke or how it affected the project]
**Solution**: [How we fixed it]
**Prevention**: [How to avoid this in the future]
**Files involved**: [List of files]
```

---

## 📚 Lessons Log

### 2026-04-09 - Testing - Interactive Prompt Mocking
**What went wrong**: Laravel Prompts `Prompt::fake()` caused infinite loops and memory exhaustion in non-interactive shell tests.
**Root cause**: The way `Prompt::fake()` interacts with the console input stream in some environments causes it to keep waiting for input that never comes, or re-render infinitely.
**Impact**: Test suite crashed with "Out of memory" or hung indefinitely.
**Solution**: Wrapped all `multiselect`, `confirm`, and `select` calls in a protected helper method (`promptSelection`) and added a static `promptRunner` callback that can be mocked in tests without using the real Prompts infrastructure.
**Prevention**: Use the custom `fakePrompts()` and `fakeCommandPrompts()` helpers for all CLI interactivity tests.
**Files involved**: `BaseInstaller.php`, `InstallCommand.php`, `Pest.php`, `InstallersTest.php`.

### 2026-04-09 - Architecture - Method Name Conflicts in Laravel Zero
**What went wrong**: Adding helper methods named `ask()` or `runCommand()` to a Command class caused fatal errors.
**Root cause**: These names are already used (and marked as public or protected) by the parent `Illuminate\Console\Command` class.
**Impact**: PHP Fatal error: "Declaration of ... must be compatible with ...".
**Solution**: Rename helpers to unique names like `promptSelection()` and `executeShellCommand()`.
**Prevention**: Check parent class methods before naming internal helpers in Laravel Command classes.
**Files involved**: `InstallCommand.php`.

### 2026-04-09 - Infrastructure - Process Timeout Issues
**What went wrong**: Heavy installations (like Laravel Sail) timed out after 300 seconds.
**Root cause**: Hardcoded timeout of 300s in `BaseInstaller::runProcess`.
**Impact**: Installation failed midway with `ProcessTimedOutException`.
**Solution**: Changed default timeout to `null` (unlimited) and allowed optional override in all run helpers.
**Prevention**: Always allow configurable timeouts for long-running shell processes in installer tools.
**Files involved**: `BaseInstaller.php`.

### 2026-04-09 - Testing - Updated Prompt Runner Signature
**What went wrong**: Changing the `BaseInstaller::$promptRunner` signature broke existing tests with a `TypeError`.
**Root cause**: The tests in `Pest.php` were using a closure with the old 3-argument signature, while the new code was passing 4 arguments (including the prompt type).
**Impact**: All tests involving prompts failed.
**Solution**: Updated `fakePrompts` and `fakeCommandPrompts` in `tests/Pest.php` to accept the `$type` argument.
**Prevention**: When changing core shared utility signatures, always check the global test helpers first.
**Files involved**: `BaseInstaller.php`, `tests/Pest.php`.

### 2026-04-09 - Testing - Simulating Side-Effects in Mocks
**Context**: End-of-process configuration (like Laravel Boost) depends on knowing which packages were actually installed by previous steps.
**The problem**: Using a simple mock for `executeShellCommand` captures the command but does not update the filesystem (e.g., `composer.json` or `artisan` file), making subsequent steps think nothing happened.
**The solution**: Enhanced `mockProcesses` in `tests/Pest.php` to simulate these side-effects by manually updating `composer.json` and creating the `artisan` file when `composer require` or `create-project` are detected.
**Benefit**: Tests are now much more realistic and easier to write, as follow-on logic correctly "sees" the results of mock installer runs.
**Files involved**: `tests/Pest.php`.

### 2026-04-09 - Architecture - Process Argument Escaping and Quotes
**What went wrong**: `vendor:publish` commands for Spatie and other packages failed to publish migrations without showing warnings.
**Root cause**: `BaseInstaller::runArtisan` used `explode(' ', $command)`, which preserved quotes within arguments (e.g., `"--provider=\"...\""`). Symfony Process then escaped these already-quoted strings, causing Laravel to fail to recognize the provider or tag names.
**Impact**: Migrations were not published, and since Artisan exited with code 0 (reporting "Nothing to publish"), the CLI didn't show any warnings.
**Solution**: Refactored `runArtisan()` to accept an array of arguments and updated all installers to use array syntax for commands with complex options.
**Prevention**: Prefer passing command arguments as arrays rather than strings to avoid brittle string splitting and double-escaping issues.
**Files involved**: `BaseInstaller.php`, `SpatiePermissionInstaller.php`, `SpatieActivitylogInstaller.php`, `SanctumInstaller.php`, `ExcelInstaller.php`.

---

## 📊 Lesson Categories

Keep track of lesson types to identify patterns:

- **API Design**: 0
- **State Management**: 0
- **Database**: 0
- **TypeScript**: 0
- **Security**: 0
- **Performance**: 0
- **Deployment**: 0

---

**Remember**: When AI makes a mistake, ALWAYS document it here!
