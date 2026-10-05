---
paths:
  - 'tests/**'
---

# Tests

## Write tests with an impartial subagent
When adding or updating tests for behavior changes, delegate test authorship to a subagent that has not seen the implementation.
Brief it with expected behaviors (spec) only: user-facing outcomes, constraints, and how to run the suite. Never show it the implementation code, migrations, or internal design. Explicitly forbid it, in the brief itself, from reading any of the application's functional code (`app/`, `database/`, `routes/`, `config/`) on its own: a subagent that peeks at the implementation writes tests that mirror it instead of pinning the agreed behavior, and drifts away from its task.
To let it match the project's test style without losing impartiality, the brief may list a small, explicit set of existing test files (paths only) it is allowed to open as style references — curate that list: only tests whose conventions and assertions you trust, never a whole directory, and never a file covering the very behavior under test (it would hand it the assertions). Open-ended "sibling tests" browsing stays forbidden.
The subagent chooses its own assertions and structure, and runs its tests itself.
If a test fails, fix the implementation rather than rewriting the test, unless the test contradicts the agreed spec.
