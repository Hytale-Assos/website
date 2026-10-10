---
paths:
  - '*'
---

# Commits

## Format

This repository uses [Conventional Commits](https://www.conventionalcommits.org/):

```
<type>(<scope>): <subject>
```

Example: `feat(points): add Points Open page computed from play sessions`

## Language

Always write in English: commit messages (subject and body), branch names, and PR titles and descriptions. Never mix French or other languages into them, even for internal or WIP commits.

## Types

| Type | Use for |
| --- | --- |
| feat | New user-facing feature or capability |
| fix | Bug fix |
| refactor | Code change that neither fixes a bug nor adds a feature |
| perf | Performance improvement |
| style | Formatting, naming, lint-only changes (no logic change) |
| test | Adding or correcting tests only |
| docs | Documentation only |
| chore | Tooling, dependencies, config, seeders, demo data |
| ci | CI workflow changes |
| build | Build system or package manager changes |

## Scope

- Lowercase and short, matching the domain touched: `user`, `auth`, `settings`, `member`, `dash`, `home`, `hytale`, `api`, `points`, `whitelist`, `deps`, `sail`, `php`, `tests`, `rules`, `git`.
- Omit the scope only when no single domain applies.

## Subject

- English, imperative mood, lowercase after the colon, no trailing period, 72 characters max.
- Describe what the commit changes, not which files it touches.

## Branches

- Branch from `develop`, never from `main`. `main` only receives merges from `develop` via pull request.
- Never push directly to `main`. The only exception is a hotfix: branch `hotfix/<slug>` from `main`, fix, open a PR to `main`, then back-merge or re-apply the fix onto `develop`.
- Name branches `<type>/<kebab-case-slug>`, matching the commit type and the change in a few words: `feat/hytale-assos`, `fix/whitelist-toast`, `chore/deps-bump`. Append the issue number when one exists: `feat/discord-linking-42`.
- Create the branch with `git switch -c <type>/<slug>` from an up-to-date `develop` (`git switch develop && git pull`, then branch).
- One branch per logical change; keep its scope small enough for a single reviewable PR.
- After the PR is merged, delete the local branch: `git switch develop && git pull && git branch -d <type>/<slug>`. Use `-D` only when the branch was never merged.
- Never rewrite history on shared branches: no force-push to `develop` or `main`.

## Structure

- Commit at every atomic unit of work: as soon as a function, component, page, route, or rule is added and the change builds (and its tests pass), commit it. Do not save up finished features into one commit: the history should narrate the whole development of the branch, step by step. The default state of the working tree is clean (`git status` empty).
- If something already committed needs rework later in the same branch, add a new commit on top instead of leaving uncommitted changes lying around.
- Keep commits atomic: one logical change each, that builds and passes its own tests.
- One increment per commit, with the tests that cover it in the same commit. A feature spreads over several increments (e.g. validation + endpoint, then its page, then its export), each building and passing its own tests.
- Calibrate granularity to the smallest coherent, testable unit: never land dead code alone (a FormRequest without its route and controller), and never bundle two concerns into one commit to save time.
- Plan the commit sequence before starting the work; it doubles as the work plan and gets adjusted as the work reveals itself.
- Intermediate commits are not individually deployable: they must build and pass their own tests, but deployability is only guaranteed at the merge boundary. Keep the seams small (a route lands in the commit right before its page).
- Never mix a refactor or chore with a feature commit. When a file straddles two logical changes, split it with `git add -p` and commit in dependency order.
- `composer.lock` changes ship with the `composer.json` change that caused them.
- Never commit secrets or `.env` values; new env keys go into `.env.example`.
- Run the narrowest relevant test suite (`vendor/bin/sail artisan test --compact --filter=...`) before committing a behavior change.
- Always re-run the complete test suite before pushing, never push on stale green.
