---
description: Start a fresh execution of a specific iteration (e.g., /execute-iteration 1.2)
argument-hint: <iteration-number, e.g. 1.2>
---

You are starting execution of iteration `$ARGUMENTS`.

## Step 1 — Get context

Read:

1. `docs/plans/iteration-$ARGUMENTS-*.md` — your iteration spec
2. Any handoff files explicitly listed in that spec's "Dependencies" section
3. Code files you'll need to touch

Do **not** read `docs/PRD.md`, `docs/plans/ROADMAP.md`, or unrelated iteration/handoff files. Context economy is the whole point of this workflow.

## Step 2 — Summarize and check in (do NOT proceed past this step on your own)

Give me a short summary of:

- The iteration's goal, scope (in and out), and acceptance criteria
- Your planned approach (especially the test plan)
- Any blockers, ambiguities, or open questions

Then **wait for my green light** before touching code.

## Step 3 — After I green-light

Follow the procedure in `docs/plans/README.md` section "Session lifecycle":

- Implement using TDD (`superpowers:test-driven-development`)
- Verify before claiming done (`superpowers:verification-before-completion`)
- Write the handoff file at `docs/plans/handoffs/iteration-$ARGUMENTS-handoff.md`
- Update `ROADMAP.md` to mark this iteration done with PR link
- Commit (`git-commit` skill) and open PR (`branch-and-pr-conventions` skill) — base branch is `feat/219-ppcp-integration` unless the spec says otherwise
- Hand back to me with PR URL + a 1–2 sentence summary + any notable handoff items
