---
description: Plan one phase's iteration specs and flesh out their GitHub issue bodies
argument-hint: <phase-number, e.g. 0>
---

You are starting a phase-planning session for Phase `$ARGUMENTS`. This is **level-3** planning — writing detailed iteration specs and populating their GitHub issue bodies for one phase, *before* any iteration in that phase executes.

## Step 1 — Get context

Read, in this order:

1. `docs/plans/README.md` — the workflow this repo uses
2. `docs/plans/ROADMAP.md` — find the Phase `$ARGUMENTS` rows and note their iteration numbers + issue numbers
3. Relevant `docs/PRD.md` section(s) for Phase `$ARGUMENTS`
4. Any prior phases' handoff files under `docs/plans/handoffs/` (only if they exist and are relevant)

## Step 2 — Summarize and check in (do NOT proceed past this step on your own)

Give me a short summary of:

- The phase's iterations (titles + 1-line goals from ROADMAP.md)
- Your planned approach skeleton for each (scope in/out, dependencies, test strategy)
- Cross-iteration handoffs you anticipate within this phase
- Any ambiguities, open questions, or blockers

Then **wait for my green light** before writing specs.

## Step 3 — After I green-light

For each iteration in the phase, in roadmap order:

1. Use `superpowers:writing-plans` to draft the iteration spec
2. Save the spec to `docs/plans/iteration-N.M-<slug>.md` using the template in `docs/plans/README.md`
3. Derive a single GitHub issue body from the spec (1–2 paragraph description + acceptance criteria + scope notes — pulled directly from the spec sections of the same names) and update the iteration's existing stub issue (number from the ROADMAP.md row) with `gh issue edit <number> --body-file <path>`

Write specs in roadmap order (0.1 → 0.2 → ...) so each can reference its predecessors' planned handoffs.

**One iteration → one spec → one populated stub issue.** Iterations are sized at the roadmap-planning stage so each fits in one PR; further decomposition into sibling sub-issues isn't a goal of this workflow.

## Step 4 — Wrap up

- Update `docs/plans/ROADMAP.md` if any iteration scope clarified or split during planning
- Commit (`git-commit` skill) — Conventional Commit, type `docs`
- Open PR. **Base branch:** `feat/219-ppcp-integration`. **Branch-name exception:** because phase-planning PRs close no iteration issues, the standard `{type}/{issue-number}-{slug}` branch convention from [`branch-and-pr-conventions`](../../.agents/skills/branch-and-pr-conventions/SKILL.md) doesn't apply. Use `docs/plan-phase-$ARGUMENTS` (e.g., `docs/plan-phase-0`) and state the exception explicitly in the PR body. All other PR conventions (template sections, BC/Compat impact note, etc.) still apply.
- PR closes none of the iteration issues by design (those close on execution PRs); reference the v4.0 milestone

Hand back to me with the PR URL and a 1–2 sentence summary of what's now ready for `/execute-iteration N.M` sessions in this phase.
