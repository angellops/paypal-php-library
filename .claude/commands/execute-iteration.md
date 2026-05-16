---
description: Start a fresh execution of a specific iteration (e.g., /execute-iteration 1.2)
argument-hint: <iteration-number, e.g. 1.2>
---

You are starting execution of **iteration `$ARGUMENTS`** for this repo.

Follow this exact procedure. Do **not** skip steps or load extra context "to be safe" — context economy is the whole point of this workflow.

## Step 1 — Read the iteration spec

Find and read the single file matching:

```
docs/plans/iteration-$ARGUMENTS-*.md
```

If exactly one file matches, that is the iteration spec. If zero or more than one file matches, stop and ask the user — do not guess.

## Step 2 — Resolve declared handoff dependencies

The iteration spec has a `## Dependencies (handoffs to read)` section. Read **only** the handoff files listed there. If the section says "None", read nothing else from `docs/plans/`.

**Do NOT** read:
- [docs/PRD.md](docs/PRD.md)
- [docs/plans/ROADMAP.md](docs/plans/ROADMAP.md)
- Other iteration specs
- Handoff files not explicitly listed as dependencies

## Step 3 — Execute the iteration

Follow the iteration spec's `## Implementation steps`. Use these skills as default:

- `superpowers:test-driven-development` — write tests first, then implementation (once testing is bootstrapped in this repo)
- `superpowers:executing-plans` — the general execution framework
- `superpowers:systematic-debugging` — only if you hit a real blocker
- `paypal` — when the work involves PayPal-specific concepts
- `php-pro` — for modern PHP idioms

Stay within the iteration's declared scope. If you discover work that should happen but is **out of scope**, note it for the handoff file — do not silently expand the iteration.

## Step 4 — Verify before claiming done

Before any "this is complete" claim, invoke `superpowers:verification-before-completion`. Run the actual test/lint/build commands and quote their output. Evidence before assertions, always.

## Step 5 — Write the handoff file

Create `docs/plans/handoffs/iteration-$ARGUMENTS-handoff.md` following the template in [docs/plans/README.md](docs/plans/README.md). Keep it short — only what the next iteration could not reasonably figure out from the code itself.

## Step 6 — Update the roadmap

Edit [docs/plans/ROADMAP.md](docs/plans/ROADMAP.md): mark this iteration's status row as ✅ done and add the (to-be-created) PR link. You may add or update entries for follow-up iterations you discovered, but **do not** invent phases.

## Step 7 — Commit + open PR

- Use the `git-commit` skill for the commit message (Conventional Commits)
- Use the `branch-and-pr-conventions` skill for branch naming, base branch, labels, and PR body
- Base branch should be `feat/219-ppcp-integration` (the v4.0 integration target) unless the iteration spec explicitly says otherwise
- PR body must use [.github/pull_request_template.md](.github/pull_request_template.md) sections
- Every closed issue gets a `Closes #N` line

## Step 8 — Hand back to the user

Report:
- The PR URL
- A 1–2 sentence summary of what shipped
- Any notable items from the handoff file the user should know about before the next iteration

**Do not** start the next iteration in the same session. The next iteration begins in a fresh session — that's the design.
