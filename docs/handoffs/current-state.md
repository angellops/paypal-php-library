# Current project state

Updated: 2026-09-04. This is evolving session state; update it when decisions or work advance. Read later user instructions and Git changes before relying on this snapshot.

## Active work and authority

- The [v4 replacement specification](../superpowers/specs/2026-09-04-v4-sdk-modernization-design.md) is the current design artifact. Its design sections were approved during brainstorming; the written document was self-reviewed and committed in `172418f`.
- Final user review of the written spec has not been explicitly recorded. The later request to archive old documents and establish ICM context does not change that status.
- No replacement implementation plan or SDK implementation was created in these sessions. The previous user boundary was to stop before implementation planning. A future request to plan/implement should use the current spec and actual source, with review status resolved in that session.
- ICM routing and the archive are established. The user subsequently approved and completed the scoped GitHub cleanup: 52 original plan issues closed as `not_planned`, with the old milestone renamed and closed. See the [verified cleanup record](../archive/v4-pre-replacement/github-cleanup.md). This authorization does not extend to SDK implementation, package publishing or live payment API calls.

## Decisions already agreed

The full requirements live in the spec; this list is a resumption index, not a replacement:

- SDK-owned REST core with separate compatibility adapters; verified seamless migration with original-backend continuity.
- Project-owned facade contracts preserved; direct abandoned-vendor SDK objects form a documented migration boundary.
- Comprehensive merchant lifecycle inventory and bounded seller onboarding/status/delegated support; REST invoicing remains separate from Adaptive Invoice services.
- Backend chosen before mutations; no cross-backend payment replay after uncertainty; durable storage for SDK-managed migration spanning requests.
- PHP 8.3 target floor, Wekoodo package name, existing namespace, fixed partner attribution, GPL continuity, 12-month v3 security-maintenance window after v4 GA.
- Security cleanup and an installable upgrade checker; acceptance based on actual contracts/lifecycles, not skipped tests or mapper counts.

## Current implementation evidence

The source baseline preceding the design was `89187eea882c660213053ced1a271a77036d4d06`. The sessions since that baseline have changed documentation only. The manifest still describes the legacy package/dependencies; see [codebase/runtime reference](../agents/codebase.md#runtime-and-tooling). No SDK tests or sandbox scenarios were run during design/context work.

The old PRD, plans, issue-generation script/map and issue template are under [the archive](../archive/README.md). Issues #286–#337 now each have the approved supersession comment and are closed as `not_planned`. Milestone 12 is `Archived: original v4.0 plan`, closed with 0 open / 52 closed issues. Labels, issue bodies/titles and unrelated issues/PRs were preserved. These closures represent superseded plans, not completed SDK work or approval of the replacement design.

## Workspace notes

The user subsequently requested that all session work be committed and merged. This includes the previously untracked brainstorming handoff and `skills-lock.json`; the latter records optional skill sources/hashes, not runtime package dependencies. Check Git for any later unrelated work before staging or switching branches.
