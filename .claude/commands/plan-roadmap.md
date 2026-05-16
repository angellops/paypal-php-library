---
description: Run the first (or refreshed) roadmap-planning session — decompose the PRD into phases + iterations and create GitHub tracking
argument-hint: (no arguments)
---

You are running a **roadmap planning session** for this repo. This is **tactical** planning (decomposing the PRD into shippable phases + iterations), not strategic planning (which produced the PRD itself).

Follow this procedure exactly. Read [docs/plans/README.md](docs/plans/README.md) section "Planning lifecycles" if you need a refresher on how this differs from iteration-spec planning or iteration execution.

## Step 1 — Load the inputs

Read, in this order:

1. [docs/plans/README.md](docs/plans/README.md) — confirm you understand the phase + iteration model
2. [docs/PRD.md](docs/PRD.md) — the full PRD (this is the one place that warrants loading it)

After this step you have the full PRD in context. **This is the heavy-context part of the session — expected and acceptable for a roadmap session, but not for any other session type.**

## Step 2 — Brainstorm the decomposition (with the user)

Invoke `superpowers:brainstorming` and align with the user on:

- **Phases** — workstream groupings that map to GitHub Milestones. A phase has a name, a 1-line goal, and a rough ordering relative to other phases.
- **Iterations** under each phase — each iteration is one PR / one execution session, closing 1–3 GitHub issues. Stay at "title + 1-line goal" granularity — **do not draft detailed iteration specs here.**
- **Dependencies** between phases or iterations (which must finish before which can start).
- **Tooling/foundational decisions** that the PRD may have deferred (PHP version target, test framework choice, linter, static analysis, PSR-0→PSR-4, license, package rename timing). These typically become very early iterations under Phase 1.

Surface trade-offs explicitly. Do not invent phases or iterations the PRD does not justify. If the PRD is ambiguous on something, ask the user — do not guess.

**Output of this step:** a phase + iteration outline agreed by the user. Nothing is written yet.

## Step 3 — Populate `ROADMAP.md`

Edit [docs/plans/ROADMAP.md](docs/plans/ROADMAP.md):

- Fill in the **Phases** table — one row per phase (name, 1-line goal, milestone reference, status `🔵 not started`)
- Add an **Iterations** subsection per phase, each with a table of iterations: `#`, slug, 1-line goal, issue(s) (to be filled in step 5), branch (TBD), PR (TBD), status (`🔵`)
- Add a starter entry to the **Change log** noting that the roadmap was populated in this session

Do **not** invent iteration MDs (`docs/plans/iteration-N.M-*.md`) in this session — that is the next session's job.

## Step 4 — Create GitHub milestone and labels

Use `gh` from the [branch-and-pr-conventions skill](.agents/skills/branch-and-pr-conventions/SKILL.md) conventions:

- Create the milestone (e.g., `v4.0`) if it doesn't exist
- Create labels:
  - `phase:1`, `phase:2`, … (one per phase)
  - `iteration:1.1`, `iteration:1.2`, … (one per iteration)
  - `type:feat`, `type:fix`, `type:chore`, `type:refactor`, `type:docs`, `type:test` (only if not already present)

Use sensible colors (phase labels one hue, iteration labels another, type labels a third).

## Step 5 — Create stub GitHub issues

For each iteration in the roadmap, create one stub GitHub issue:

- **Title:** `[Iteration N.M] <1-line goal>`
- **Body:** Use [.github/ISSUE_TEMPLATE/iteration-issue.md](.github/ISSUE_TEMPLATE/iteration-issue.md) structure. Set `Iteration MD: TBD` — the actual spec is written in a later session. Leave acceptance criteria as `- [ ]` placeholders.
- **Labels:** `phase:N`, `iteration:N.M`, plus the most likely `type:*` (refine later)
- **Milestone:** the milestone created in step 4

After creation, edit `ROADMAP.md` to fill the `Issue(s)` column with the new issue numbers.

If an iteration is expected to close more than one issue, create the additional issues now too and link all of them in the roadmap row.

## Step 6 — Recommend a starting point

Look at the roadmap you just built and recommend to the user **which iteration to start with first**. Usually this is `1.1` — but flag if there are tooling/foundation iterations that should precede the first "real" feature work, or if any phase ordering is non-obvious.

## Step 7 — Commit and hand back

This planning session's output is itself a PR-worthy change. Use the `git-commit` and `branch-and-pr-conventions` skills:

- Create a branch: `chore/<issue-number>-populate-roadmap` (create a tracking issue for "Populate v4.0 roadmap" first if one doesn't already exist)
- Commit the roadmap + any related changes
- Open a PR against `feat/219-ppcp-integration` using the PR template

Hand back to the user with:

- The PR URL
- The milestone URL
- The full list of created issue numbers (with titles)
- Your recommended first iteration
- Any open questions or tooling decisions the user still needs to make before iteration 1.1 can start

**Do not** start writing iteration specs or executing iterations in this session. Those are separate sessions, by design.
