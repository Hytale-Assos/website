---
paths:
  - 'tests/**'
---

# Tests

## Write tests with an impartial subagent
When adding or updating tests for behavior changes, delegate test authorship to a subagent that has not seen the implementation.
Brief it with expected behaviors (spec) only: user-facing outcomes, constraints, and how to run the suite. Never show it the implementation code, migrations, or internal design.
The subagent chooses its own assertions and structure, and runs its tests itself.
If a test fails, fix the implementation rather than rewriting the test, unless the test contradicts the agreed spec.
