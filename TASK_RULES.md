Purpose: a reusable task template for this repo that minimizes token usage per task.

## Instructions for the AI Agent (MANDATORY — read first)

### 1. Token efficiency
- Be terse: no restating the request, no play-by-play narration, no final summaries unless asked. Output only decisions, diffs, results.
- Delegate all reading to subagents (`.claude/skills/*/SKILL.md`, template/example files, any codebase search). Subagent returns a ≤20-line distilled conclusion; never dump file contents into main context.
- Read surgically: `Read` only files you are about to edit, at most once; afterwards reference them as `path:line`.
- Never repeat work: no re-running known commands, no blind retries — diagnose, then fix.
- Batch independent tool calls in one parallel block.

### 2. Clarify ambiguities BEFORE writing code
List every underspecified point and either ask the user, or explicitly authorize inference from existing code (name the files). Common ambiguities: route names/prefixes, daily_limit / subscription_limit values, observer sets, exact file names, response field names.

### 3. Work in phases
1. **Plan**: subagent explores and returns a numbered create/modify file list with one-line descriptions. For well-specified tasks, proceed without waiting for approval.
2. **Implement**: one subagent per cohesive group (e.g. "model + migration", "controller + routes + tests").
3. After each phase, append a brief status (progress, decisions, blockers) to `storage/larbox/_task_notes.md` — it is the source of truth if context is compacted.

---

---

## Rules
- Write readable code in my style
- Do NOT run tests or artisan commands unless asked
- Do NOT refactor or improve existing code
- If anything is ambiguous — ask me
