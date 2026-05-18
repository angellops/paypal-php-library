# Plans — workflow guide

How v4.0 (and future major-version) work is broken down, tracked, and executed in clean-context AI sessions.

> **Audience:** any agent (human or AI) about to plan, execute, or review work in this repo. If you're starting an iteration, read this **once** to understand the system, then read your iteration spec and stop.

---

## The hierarchy

```
PRD                                — vision (docs/PRD.md)
└── Phase                          — workstream / semantic grouping
    │   • Tracked in ROADMAP.md and as a GitHub Milestone
    │   • Has NO standalone MD file beyond ROADMAP.md
    │
    └── Iteration                  — the executable atom
        │   • 1 iteration = 1 AI session = 1 PR = 1 MD file
        │   • Closes 1–3 GitHub issues per PR
        │   • Spec: docs/plans/iteration-N.M-<slug>.md
        │   • Handoff (output): docs/plans/handoffs/iteration-N.M-handoff.md
        │
        └── GitHub Issue(s)        — granular deliverables (1–3 per iteration)
```

**Why two levels?** Phase gives semantic context for humans and the roadmap; iteration is the actually-executable unit small enough for one clean context window.

**Sizing rule of thumb:**
- 1 iteration ≈ 1–2 story points ≈ one focused session ≈ one PR
- If you want to split: it's multiple iterations under the same phase
- If 1–3 issues naturally ship together in one PR: that's one iteration
- 4+ issues per iteration → split it

---

## File conventions

| File | Purpose | When written | When read |
|---|---|---|---|
| [`docs/PRD.md`](../PRD.md) | Vision & scope for v4.0 | Authored once, edited rarely | During **planning** only — never auto-loaded during execution |
| [`docs/plans/README.md`](README.md) | This file — the workflow itself | Updated when workflow changes | Once per agent, then forgotten |
| [`docs/plans/ROADMAP.md`](ROADMAP.md) | Phase + iteration index | Updated after every iteration | During planning; **not** read by iteration sessions |
| `docs/plans/iteration-N.M-<slug>.md` | The spec for one iteration | Written in **phase-batch** sessions before that phase's first iteration executes | The session executing iteration N.M |
| `docs/plans/handoffs/iteration-N.M-handoff.md` | Delta produced by completing N.M | Written by the iteration as part of "done" | Only by iterations that explicitly list it as a dependency |

---

## The context-economy invariant

This is the whole point of the workflow.

**An agent executing iteration N.M reads:**
- ✅ Its own iteration spec (`docs/plans/iteration-N.M-<slug>.md`)
- ✅ Any handoff files its spec **explicitly lists** as dependencies
- ✅ The code files it needs to touch
- ✅ `AGENTS.md` (auto-loaded via `CLAUDE.md`'s `@import`)

**An agent executing iteration N.M does NOT read:**
- ❌ `docs/PRD.md`
- ❌ `docs/plans/ROADMAP.md`
- ❌ Other iteration specs
- ❌ Handoff files that aren't declared dependencies
- ❌ Anything in `~/.claude/plans/` or auto-memory unless directly referenced

If you find yourself wanting to read the PRD mid-iteration, your iteration spec is probably underspecified — flag it and fix the spec, don't paper over it.

---

## Iteration spec template

Every `iteration-N.M-<slug>.md` file should have these sections:

```markdown
# Iteration N.M — <title>

**Phase:** N — <phase name>
**GitHub issues:** #A, #B, #C
**Branch:** `{type}/<primary-issue-#>-<slug>`
**Base branch:** feat/219-ppcp-integration (or release for v3.x hotfixes)

## Goal
<1–2 sentence statement of what this iteration ships>

## Dependencies (handoffs to read)
- docs/plans/handoffs/iteration-X.Y-handoff.md — <why we need it>
- (or "None" if this is iteration 1.1 or otherwise has no prior dependencies)

## Scope
**In scope:**
- <bullet>

**Out of scope (deferred to later iterations):**
- <bullet>

## Implementation steps
1. <step>
2. <step>

## Acceptance criteria
- [ ] <criterion>
- [ ] All tests pass: `vendor/bin/phpunit` (once testing is bootstrapped)

## Files of interest
- `src/...` — <why>

## Out-of-scope, do not touch
- <explicit boundaries — files, behaviors, or concerns that are NOT this iteration's job>
```

---

## Handoff file template

Every iteration must write a handoff file as part of "done." Keep it small — only what the *next* iteration could not reasonably figure out from the code itself.

```markdown
# Iteration N.M handoff

## What changed (high level)
- <bullet>

## New public surfaces
- `angelleye\PayPal\Rest\Orders\V2\OrdersClient` — entry point with `create()`, etc.
- <bullet>

## Known limitations / deferred follow-ups
- <bullet> (likely to be picked up by iteration N.M+1)

## Files of interest for next iteration(s)
- `src/...`

## Surprises / non-obvious decisions
- <anything a fresh agent would not predict from reading the code>
```

**Rule:** if there are no surprises and the next iteration could trivially figure things out from the code, the handoff can be a 3-line stub. Length is not virtue.

---

## Session lifecycle (one iteration, start to finish)

1. **Cold session opens.** The agent has only `AGENTS.md` (via `CLAUDE.md`) loaded.
2. **Invoke the iteration** — either typed (`start iteration 1.2`) or via slash command (`/execute-iteration 1.2`).
3. **Agent reads** the iteration spec → resolves declared handoff deps → reads code as needed.
4. **Agent works** following the iteration's implementation steps. Uses `superpowers:test-driven-development` and `superpowers:executing-plans` as default. Uses `superpowers:systematic-debugging` if blocked.
5. **Before claiming "done":** runs `superpowers:verification-before-completion` — actual commands, actual output. No assertions without evidence.
6. **Agent writes** `docs/plans/handoffs/iteration-N.M-handoff.md`.
7. **Agent commits** with `git-commit` skill (Conventional Commit format).
8. **Agent opens PR** using `branch-and-pr-conventions` skill (correct base, template body, `Closes #N` lines, labels).
9. **Agent updates `ROADMAP.md`** to mark the iteration's status row as ✅ done with PR link.
10. **User reviews PR.** Merge closes the issues automatically. Session ends.

The next iteration starts in a **completely fresh session** with no memory of this one — only what was captured in the handoff file.

---

## Planning lifecycles

There are **three** distinct levels of planning. Don't conflate them — they have different inputs, outputs, and session requirements.

### 1. Strategic planning — the PRD

- **Input:** vision, stakeholder requirements
- **Output:** [docs/PRD.md](../PRD.md)
- **Scope:** "What is this version? What does success look like?"
- **Cadence:** once per major version, edited rarely
- **Not what this workflow concerns itself with** — the PRD is an upstream artifact this workflow consumes

### 2. Tactical planning — the roadmap

- **Input:** PRD
- **Output:** populated [docs/plans/ROADMAP.md](ROADMAP.md) + GitHub milestone + labels + stub issues
- **Scope:** "What phases? What iterations under each? In what order?"
- **Cadence:** once at the start of a major version, then small adjustments as iterations reveal needed splits/merges
- **Slash command:** `/plan-roadmap` (see `.claude/commands/plan-roadmap.md`)
- **Stays at title + 1-line goal per iteration.** Does **not** write detailed iteration specs — those are produced later by per-phase planning sessions (level 3 below), which also flesh out the stub issue bodies.

### 3. Phase planning — iteration specs, one phase at a time

- **Input:** all iteration rows for one phase from the roadmap, plus relevant PRD section(s), plus prior phases' handoff files if any
- **Output:** N iteration spec MDs (`docs/plans/iteration-N.M-<slug>.md`) **and** populated GitHub issue bodies for that phase. Each iteration's existing level-2 stub is rewritten with real content here. Iterations that `to-issues` decomposes into 2–3 vertical slices spawn additional GitHub issues — the existing stub becomes the **primary** slice, and the additional slices are created as new issues on the same milestone with the same phase + `iteration` labels (each linked back via `Sub-issue of #<primary>` in its body). The iteration's ROADMAP.md row is updated to list all issue numbers in its Issue column.
- **Scope:** "Exactly what does each iteration in this phase ship, in what order, with what tests?"
- **Cadence:** one batch per phase, written *before* that phase's first iteration executes
- **Slash command:** `/plan-phase N` (see `.claude/commands/plan-phase.md`)
- **Skills used:** `superpowers:writing-plans` (for the spec content) + the third-party [`to-issues`](https://www.skills.sh/mattpocock/skills/to-issues) skill from Matt Pocock (for decomposing each iteration into 1–3 vertical-slice GitHub issue bodies). Install `to-issues` once, before the first phase-planning session, then use both skills together from then on.

### Why split levels 2 and 3 into separate sessions?

Context economy and scope focus. A roadmap-planning session (level 2) needs the full PRD loaded — a heavy context cost paid once to lay out all phases and iterations. A phase-planning session (level 3) needs only the one phase's roadmap rows + relevant PRD section(s) + prior phases' handoffs — much lighter, and lets each session concentrate on one phase's design end-to-end without mixing in unrelated work. Splitting keeps each session focused on one concern at one altitude.

---

## Quick reference — where things live

| Thing | Location |
|---|---|
| Vision | [docs/PRD.md](../PRD.md) |
| Workflow explainer (this file) | [docs/plans/README.md](README.md) |
| Phase + iteration tracker | [docs/plans/ROADMAP.md](ROADMAP.md) |
| Iteration specs | `docs/plans/iteration-N.M-<slug>.md` |
| Handoff files | `docs/plans/handoffs/iteration-N.M-handoff.md` |
| Repo conventions, tech stack | [AGENTS.md](../../AGENTS.md) |
| Branch + PR rules | [.agents/skills/branch-and-pr-conventions/SKILL.md](../../.agents/skills/branch-and-pr-conventions/SKILL.md) |
| Commit message format | [.agents/skills/git-commit/](../../.agents/skills/git-commit/) |
| PR template | [.github/pull_request_template.md](../../.github/pull_request_template.md) |
| Iteration issue template | [.github/ISSUE_TEMPLATE/iteration-issue.md](../../.github/ISSUE_TEMPLATE/iteration-issue.md) |
| Slash command to plan the roadmap (level 2) | `.claude/commands/plan-roadmap.md` |
| Slash command to plan one phase's iteration specs (level 3) | `.claude/commands/plan-phase.md` |
| Slash command to start an iteration | `.claude/commands/execute-iteration.md` |
