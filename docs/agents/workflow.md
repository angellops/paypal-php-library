# Project workflow contracts

Use the user's authorized scope and existing approvals. These contracts keep work reviewable across harnesses; installed process skills may provide execution detail. Do not create an additional plan or ask again for approval solely because context files were reorganized.

## Inputs

| Input | Read only the relevant portion |
| --- | --- |
| [Current state](../handoffs/current-state.md) — Layer 4 | Active artifact, approval status, next-step boundary |
| [Codebase map](codebase.md) — Layer 3 | The affected integration surface, callers and runtime notes |
| [Branch policy](branching.md) — Layer 3 | Select the correct base/PR target before making changes; distinguish merges from releases |
| [Documentation router](../CONTEXT.md) — Layer 1 | Select relevant sections of the current spec rather than loading all documents |
| Affected source and caller/template — implementation evidence | Actual signatures, data shapes, transport and dependencies |
| Current task's spec/plan/handoff — Layer 4 | Only the artifact selected by current state or the user's request; archived plans are excluded |

## Process and outputs

| Task | Process | Reviewable output |
| --- | --- | --- |
| Design | Preserve already approved decisions; resolve substantive new boundaries; distinguish source facts from proposals | Updated design under `docs/superpowers/specs/`, with explicit approval status |
| Implementation planning | Use the reviewed design and actual source; make acceptance evidence explicit; identify dependencies and unverified assumptions | A new plan under `docs/superpowers/plans/` when requested, not recycled archived phases |
| Implementation | Follow the authorized task/plan; preserve public contract boundaries; verify affected behavior | Scoped code/docs changes and actual verification results |
| Bug investigation | Reproduce safely; trace the specific entry point and API family; establish cause before changing behavior | Findings or a scoped fix with evidence and remaining limitations |
| Context/documentation maintenance | Update canonical files, rebase moved links, retain historical authority labels | Updated routers/references/state or archive; no incidental SDK changes |

Review gates track actual decisions. Present completed artifacts for review where the task requires it. Do not treat a spec's existence as approval to implement it, or a request to archive old plans as approval of a new plan. If a previously requested stop boundary is superseded by a later explicit user instruction, record that change instead of preserving the stop forever.

## Verification

Read actual project configuration before choosing commands. At context setup there is no tracked automated test/CI setup; future work must update this fact when tooling lands.

- Documentation/context: `git diff --check`; validate local links, root shim and task routes; run the available ICM audit for structure changes.
- PHP syntax: `php -l path/to/changed.php` checks syntax only and does not execute an API call. Confirm the PHP version used when runtime compatibility matters.
- Behavior changes: run relevant existing tests, or establish focused contract/lifecycle evidence under the implementation task. Syntax checks alone do not prove payment correctness.
- Packaging: inspect the manifest and validate installation in an appropriate isolated environment when packaging changes are in scope. Do not run a broad dependency update for a documentation task.
- Sandbox: record environment, account capabilities, scenario, result and skipped/blocked cases. Never equate a skipped scenario with a pass or source review with live verification.

When the local ICM skill is available, run:

```bash
python .agents/skills/icm/scripts/audit_routed_context.py .
```

The skill directory is optional tooling, not a required tracked dependency. Without it, use the portable manual checklist in [ICM conventions](icm-conventions.md#validation).

## Completion and handoff

Report changed behavior/documents, verification actually performed, material limitations and the next authorized step. Keep review status explicit. Preserve unrelated workspace changes; stage only the task's files when committing is requested or part of the authorized workflow.

Update [current state](../handoffs/current-state.md) when active artifacts, approval status or next-step boundaries change. For a substantial handoff, create a dated file under `docs/handoffs/` containing the objective, approved decisions, source/commit baseline, evidence, unresolved questions and next action. Never put credentials in a handoff. Update the handoff router to keep old instructions from becoming the default starting point.
