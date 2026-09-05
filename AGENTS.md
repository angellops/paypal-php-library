# Project agent instructions

This repository contains the Angell EYE PayPal PHP library and its Wekoodo v4 modernization design. Work from the checked-in source for existing behavior and the current specification for intended behavior; they are not yet the same thing.

## Start here

1. Read [CONTEXT.md](CONTEXT.md) and follow the route matching the task.
2. For ongoing modernization work, read [current state](docs/handoffs/current-state.md) before choosing a spec, plan, or handoff.
3. Inspect Git status and applicable directory instructions before editing. Preserve unrelated work, local credentials, and ignored tooling.

This file is the canonical shared entrypoint. `CLAUDE.md` only imports it. Context is kept in the repository so another harness can follow the same routes.

## Working rules

- Follow the user's current scope and existing approvals. A request to organize documentation does not authorize SDK implementation, publishing, payments, or external issue updates.
- Use scoped context: load the named reference and relevant spec sections, then inspect the affected code and callers. Do not read every handoff, archived plan, or skill by default.
- The active spec governs v4 intent; source and observed tests govern current behavior. Historical documents under `docs/archive/` are evidence only, never an execution queue.
- Follow the [branch policy](docs/agents/branching.md): before v4 GA, shared docs and v3-compatible fixes target `main`; v4 implementation targets `v4` through topic-branch PRs.
- Preserve the separation between Classic NVP, Adaptive XML, existing REST invoicing/facades, and Payflow. Verify field, response, consent and resource-origin semantics before claiming compatibility.
- Never resolve an uncertain payment by replaying it on another backend. Do not infer success from an HTTP response, approval redirect, or missing error alone.
- Keep credentials, tokens, card data and customer data out of logs, fixtures and committed context. Inspect example configuration rather than private configuration. Demos may submit real API operations; running a demo is not a harmless unit test.
- Use relevant available skills without copying their instructions into project context. Local `.agents/`, `.claude/` and `.codex/` tooling is optional and ignored; the shared workflow must remain understandable without it.
- Verify changes with checks appropriate to the work and report what actually ran. Do not claim planned checks exist, skipped scenarios passed, or sandbox behavior was verified by source review.
- Update the relevant router when paths change. Put stable guidance in `docs/agents/`, current progress in `docs/handoffs/`, and design/plan artifacts in `docs/superpowers/`.

See [workflow and verification](docs/agents/workflow.md) for task inputs, outputs and completion expectations.
