# Project ICM conventions

The project's Interpretable Context Methodology structure uses **coexist-process** mode. It adds portable routing around the existing `docs/superpowers/` design/plan process. It does not invent a second pipeline, agent roster, numbered stages or automated delegation.

## Ownership and placement

| Role | Canonical location | Maintenance rule |
| --- | --- | --- |
| Layer 0 instructions | Root `AGENTS.md` | Shared project instructions; keep short |
| Harness shim | Root `CLAUDE.md` | Exactly `@AGENTS.md`; no duplicated instructions |
| Layer 1 routing | Root `CONTEXT.md`, `docs/CONTEXT.md`, `docs/handoffs/CONTEXT.md` | Route by task to exact files/sections; avoid embedding the referenced documents |
| Layer 2 control | `docs/agents/workflow.md` plus the selected spec/plan | Inputs, process, outputs, verification and review boundaries; no parallel `stages/` |
| Layer 3 references | `docs/agents/` | Stable codebase/process/context conventions; update factual notes when code changes |
| Layer 4 working artifacts | `docs/superpowers/`, `docs/handoffs/` | Design/plan outputs and evolving session state, with explicit review status |
| Historical evidence | `docs/archive/` | Retired artifacts with provenance and a link to their replacement |

A reviewed design remains a product artifact in its established spec tree even when it constrains implementation. Link to it rather than copying it into a stable reference. This mapping follows layer properties and preserves the existing process's ownership.

## Loading and extension

Start with the root entrypoint and router, then load only the current state and references required for the task. Prefer relevant spec sections and specific source methods/callers over whole-tree reads. Keep initial routing small enough to scan; split a growing reference by responsibility and add a route to it.

No file requires a harness-private memory path, personal absolute path, or installed skill to understand the project. Ignored local skill/tool directories are optional execution aids. Durable decisions must be recorded in the workspace.

Root `skills-lock.json` records the selected optional skill sources and hashes for reproducibility. It does not install those skills automatically and is not an SDK runtime dependency. The project workflow and manual validation checklist remain usable without them.

Only add nested `AGENTS.md` files when a subtree needs additional scoped instructions. Add nested `CONTEXT.md` routers when they reduce navigation cost. Do not repeat root rules or introduce numbered folders unless there is an actual ordered workflow or selectable reference roster.

When archiving, preserve provenance, mark artifacts historical, relocate their supporting artifacts where appropriate, and rebase relative links. Literal old paths and unavailable personal-memory references inside historical documents may remain as historical evidence; they must not become current context dependencies.

## Validation

Run the ICM audit when available (command in [workflow](workflow.md#verification)), then manually verify:

1. `AGENTS.md` is canonical and `CLAUDE.md` only imports it.
2. Root routing reaches current state, relevant source, design, workflow and archive without loading all of them.
3. Task inputs name concrete files or sections and produce reviewable artifacts.
4. Stable references, evolving state and superseded artifacts have distinct homes.
5. New/moved local links resolve; no active route selects archived plans for execution.
6. Current/target behavior, approval status and actual verification remain distinguishable.
7. `git diff --check` passes and unrelated files remain untouched.

ICM methodology attribution and the local audit implementation are documented in the optional `.agents/skills/icm/SKILL.md`. This project file records the adopted layout, not a dependency on that local installation.
