# Project context router

Start with [AGENTS.md](AGENTS.md). Select the route for the task; links are context to load on demand, not a required whole-repository reading list.

## Task routing

| Task | Read next | Scope |
| --- | --- | --- |
| Resume work or determine what is approved | [Current state](docs/handoffs/current-state.md) | Status, active artifact, next-step boundary; check Git for changes since the recorded state |
| Locate SDK code and integration surfaces | [Codebase map](docs/agents/codebase.md) | Select the affected class, caller, template or demo |
| Review or refine v4 design | [Documentation router](docs/CONTEXT.md) | Current specification; choose only relevant numbered sections |
| Plan or implement an authorized change | [Workflow](docs/agents/workflow.md) | Inputs, reviewable outputs, authorization and verification; then relevant source/spec |
| Investigate a bug or regression | [Codebase map](docs/agents/codebase.md), [workflow verification](docs/agents/workflow.md#verification) | Existing behavior first; reproduce without initiating unintended payments |
| Change packaging, runtime or local setup | [Codebase map](docs/agents/codebase.md#runtime-and-tooling) | Actual Composer/autoload files; distinguish local environment from v4 target |
| Find prior reasoning | [Handoff router](docs/handoffs/CONTEXT.md) | Current state first, selected dated handoff only when needed |
| Investigate superseded PRD/plans/issues | [Archive index](docs/archive/README.md) | Historical evidence only |
| Maintain agent context | [ICM conventions](docs/agents/icm-conventions.md) | Layer ownership, scoped loading, link maintenance and audit |

## Context layers

| Layer | Project location |
| --- | --- |
| 0 — identity/instructions | [AGENTS.md](AGENTS.md); [CLAUDE.md](CLAUDE.md) is an import shim |
| 1 — navigation | This router, [docs/CONTEXT.md](docs/CONTEXT.md), [handoff router](docs/handoffs/CONTEXT.md) |
| 2 — task control | [Workflow contracts](docs/agents/workflow.md), with the applicable reviewed spec/plan selected per task |
| 3 — stable references | [docs/agents/](docs/agents/) and the affected source/API references |
| 4 — working artifacts | [docs/superpowers/](docs/superpowers/) for specs/plans; [docs/handoffs/](docs/handoffs/) for evolving session state |

The ICM mode is **coexist-process**. The existing spec/plan process owns multi-step work; there is no parallel `stages/` pipeline. See [ICM conventions](docs/agents/icm-conventions.md) for how to extend this structure.
