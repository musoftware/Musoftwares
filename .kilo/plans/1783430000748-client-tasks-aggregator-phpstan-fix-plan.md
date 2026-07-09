# Plan: Fix PHPStan Errors for `ClientTasksAggregatorController`

## Goal
Clear all 6 PHPStan `relationExistence` errors reported in `app/Http/Controllers/Client/ClientTasksAggregatorController.php` so the static analysis pipeline passes.

## Investigation Summary

PHPStan (Level 1 + Larastan) reports 6 errors, all `larastan.relationExistence`:

| Line | Controller code | Model claimed missing | Relation actually defined? |
|------|-----------------|----------------------|----------------------------|
| 44   | `->with(['project:…', 'task_todo_items:…'])` on `Task::query()` | `project` (Task) | **YES** — `app/Models/Task.php:230` |
| 44   | same | `task_todo_items` (Task) | **YES** — `app/Models/Task.php:194` |
| 58   | `whereDoesntHave('task_todo_items')` on Task | `task_todo_items` (Task) | **YES** — line 194 |
| 58   | `orWhereHas('task_todo_items', …)` on Task | `task_todo_items` (Task) | **YES** — line 194 |
| 66   | `->with(['project:…', 'task:…'])` on `Todo::query()` | `project` (Todo) | **NO — genuinely missing** |
| 66   | same | `task` (Todo) | **YES** — `app/Models/Todo.php:83` |

Two distinct situations:

1. **Genuine missing relation.** `App\Models\Todo` has no `project()` method, although the schema (`todos.project_id`) and downstream code (`$todo->project->project_name` on line 109 of the controller) clearly assume one.
2. **False-positive / cache-related misses on relations that DO exist.** All three relations on `Task` and `task()` on `Todo` are present in source, but Larastan reports them as missing. The repository already contains 50+ `larastan.relationExistence` baselines in `phpstan-baseline.neon`, so the project's standing convention is to silence such misses via the baseline rather than try to coerce Larastan.

## Approach

Follow the existing project convention (see `phpstan-baseline.neon` lines 47–67, 401–899, 3285–3301) — repair what is genuinely wrong, baseline the rest.

## Ordered Tasks

1. **Add the missing `project()` relation to `Todo`.**
   - File: `app/Models/Todo.php`
   - Insert next to the existing `task()` method (around line 84):
     ```php
     public function project()
     {
         return $this->belongsTo(Project::class);
     }
     ```
   - This also makes the eager-load `->with(['project:id,project_name,archived', 'task:id,task_name'])` on line 66 and the runtime access `$todo->project->project_name` on line 109 statics-safe and resolves the real defect.

2. **Add 5 baseline entries to `phpstan-baseline.neon`** for the remaining `relationExistence` errors on the already-defined relations. Group them under `app/Http/Controllers/Client/ClientTasksAggregatorController.php`, following the exact format used elsewhere in the baseline:
   ```neon
           -
               message: '#^Relation ''project'' is not found in App\\Models\\Task model\.$#'
               identifier: larastan.relationExistence
               count: 1
               path: app/Http/Controllers/Client/ClientTasksAggregatorController.php

           -
               message: '#^Relation ''task_todo_items'' is not found in App\\Models\\Task model\.$#'
               identifier: larastan.relationExistence
               count: 1
               path: app/Http/Controllers/Client/ClientTasksAggregatorController.php

           -
               message: '#^Relation ''task'' is not found in App\\Models\\Todo model\.$#'
               identifier: larastan.relationExistence
               count: 1
               path: app/Http/Controllers/Client/ClientTasksAggregatorController.php
   ```
   Note: the two `task_todo_items` errors at lines 44 and 58 share the same message; declare the entry with `count: 1` and let the file's per-message count naturally combine, OR add two separate `count: 1` entries grouped by file. Place the new entries in alphabetical order under the `app/Http/Controllers/...` bucket of the baseline (or at the end of the file, mirroring how recent additions were appended).

3. **Verify.**
   - Run `vendor/bin/phpstan analyse --no-progress` locally.
   - Confirm all 6 errors disappear and no new errors are introduced.
   - If the run is clean, run `vendor/bin/phpstan analyse --generate-baseline` to double-check nothing else regressed.
   - Sanity-check the `Todo::project()` addition: load a Todo instance in tinker/`php artisan tinker` and confirm `$todo->project` returns a Project (or null) without an error.

## Files to Modify

- `app/Models/Todo.php` — add `project()` relation.
- `phpstan-baseline.neon` — append the 3 baseline entries above (the second `task_todo_items` message from line 58 is the same string as line 44, so 3 distinct message strings cover all 6 original errors).

## Out of Scope

- Refactoring `ClientTasksAggregatorController` itself — it is read-only and follows `ClientProjectTaskController::tasksIndex`.
- Adding `tests` — no test exists for the aggregator, and the change is purely static-analysis-driven.
- Investigating why Larastan misses relations that exist in source — baselining matches established repo practice; a deeper Larastan investigation can be a separate task.

## Risks

- `Todo::project()` does not currently exist, so any code elsewhere that relied on `$todo->project` would have been failing at runtime. A quick grep before implementation confirms this is the only consumer. (If other consumers exist, adding the relation may *change* runtime behavior, but it would be a fix, not a regression.)
- Adding baseline entries silences future regressions on those exact relations for this exact file. If the `Task` model relation names change, the baseline will need to be regenerated.

## Open Questions

None — the design decisions above mirror the project's standing PHPStan handling convention and require no user input.
