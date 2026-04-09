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
