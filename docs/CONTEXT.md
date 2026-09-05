# Documentation context router

Read [current state](handoffs/current-state.md) for review/implementation status. The current design is [v4 SDK modernization](superpowers/specs/2026-09-04-v4-sdk-modernization-design.md). A design requirement is not evidence of an implemented feature.

## Task routing

| Question | Spec sections or reference |
| --- | --- |
| Outcome, scope, components | Spec §§1–2 |
| Merchant operations and bounded platform coverage | Spec §3; invoicing §3.1, disputes/webhooks §3.2, tracking/callbacks §3.3, platform §3.4 |
| Existing signatures, field mapping, vendor object boundary | Spec §4; inspect the corresponding source using [codebase map](agents/codebase.md) |
| Historical resources, routes, durable state, retries | Spec §§4.4–5 |
| Native PHP API and HTTP transport | Spec §6 |
| Browser flows, webhooks, IPN and fulfillment | Spec §7 |
| Security, runtime, package migration, upgrade checker | Spec §8 |
| Release acceptance and source evidence | Spec §§9–10 |
| Development process and actual verification | [Workflow](agents/workflow.md) |
| Session history | [Handoff router](handoffs/CONTEXT.md) |
| Superseded PRD and 52 original plans | [Archive](archive/README.md); never select them for execution |

Specs live in `docs/superpowers/specs/`. Future implementation plans belong in `docs/superpowers/plans/` when requested; that directory need not exist before the first plan. Keep approval status in each artifact and summarize the current one in the state file. A newer timestamp alone does not confer authority.

The separate [documentation/](../documentation/) directory contains legacy user-facing HTML documentation. It is not the v4 design or an agent instruction layer.
