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

Verify the third-party `to-issues` skill (Matt Pocock, [skills.sh](https://www.skills.sh/mattpocock/skills/to-issues)) is available in the discoverable-skills list for this session. It should already be — the repo tracks it under `.agents/skills/to-issues/` per the convention in `.gitignore` and `skills-lock.json`. **If it is NOT available, stop and ask the user to install it.** Do not auto-install third-party skills; that is a local-environment mutation + supply-chain action that requires explicit human approval, not a side effect of running this slash command. This skill is the workhorse for decomposing each spec into vertical-slice GitHub issue bodies.

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
3. Use the `to-issues` skill to derive **1–3** vertical-slice issue bodies from the spec
4. Populate the GitHub issue tracker:
   - **If `to-issues` produced exactly 1 body:** update the iteration's existing stub issue (number from the ROADMAP.md row) with `gh issue edit <number> --body-file <path>`.
   - **If `to-issues` produced 2 or 3 bodies:** treat the existing stub as the *primary* slice — update it with the first body via `gh issue edit`. Then `gh issue create` each additional body, applying the same milestone (`v4.0`) and labels (the iteration's `phase-N` + `iteration`) so they cluster with the rest. Each newly-created issue's body should open with `Sub-issue of #<primary>` so the tracker link back is explicit.
5. If new issues were created in step 4, update the iteration's ROADMAP.md row to list all issue numbers in the Issue column (e.g., `#239, #284`) and update the iteration spec's `**GitHub issues:**` frontmatter line to match.

Write specs in roadmap order (0.1 → 0.2 → ...) so each can reference its predecessors' planned handoffs.

## Step 4 — Wrap up

- Update `docs/plans/ROADMAP.md` if any iteration scope clarified or split during planning
- Commit (`git-commit` skill) — Conventional Commit, type `docs`
- Open PR (`branch-and-pr-conventions` skill) — base branch is `feat/219-ppcp-integration`
- PR closes none of the iteration issues by design (those close on execution PRs); reference the v4.0 milestone

Hand back to me with the PR URL and a 1–2 sentence summary of what's now ready for `/execute-iteration N.M` sessions in this phase.
