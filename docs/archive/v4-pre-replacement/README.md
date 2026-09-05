# Superseded v4 PRD and plan workflow

Archived on 2026-09-04 after the replacement design was written. The historical baseline was `89187eea882c660213053ced1a271a77036d4d06`; supersession notices were added in `172418f` before relocation.

| Original location | Archived artifact |
| --- | --- |
| `docs/PRD.md` | [PRD.md](PRD.md) |
| `docs/v4-plans/` | [Plan index and 52 phase files](v4-plans/README.md) |
| `scripts/create-v4-issues.sh` | [Historical issue-creation script](scripts/create-v4-issues.sh) |
| `scripts/v4-issue-map.tsv` | [Historical issue mapping](scripts/v4-issue-map.tsv) |
| `.github/ISSUE_TEMPLATE/v4-plan-handoff.md` | [Historical issue template](issue-template/v4-plan-handoff.md) |

The [replacement specification](../../superpowers/specs/2026-09-04-v4-sdk-modernization-design.md) governs current v4 intent. Its review status and next-step boundary are recorded in [current state](../../handoffs/current-state.md).

Do not execute the archived plans or issue-creation script. The script is preserved as historical source and still contains original path assumptions and external side effects. The issue map describes previous issue creation, not a new execution queue. No GitHub issue or milestone was changed by this archive operation.

After the archive operation, the user separately approved [GitHub cleanup](github-cleanup.md): issues #286–#337 were closed as `not_planned` with supersession comments, and milestone 12 was renamed `Archived: original v4.0 plan` and closed. Verification found 0 open and 52 closed issues in that milestone. Existing labels and merged PRs were preserved.

Relative Markdown navigation has been rebased for the new location. Historical prose, code samples, old filesystem paths, placeholders and personal-memory references are retained as evidence of the previous design; they are not portable dependencies of the current agent context.
