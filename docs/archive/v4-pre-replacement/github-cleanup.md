# Original v4 GitHub issue cleanup

Completed with explicit user approval on 2026-09-04 (America/Chicago).
Verified against GitHub at `2026-09-05T01:07:31.721590+00:00` (UTC).

## Scope and results

Repository: [angellops/paypal-php-library](https://github.com/angellops/paypal-php-library).
The [archived issue map](scripts/v4-issue-map.tsv) identifies exactly 52 plan issues, numbers **286 through 337 inclusive**. Before cleanup, all were open, had no comments, referenced the original PRD/plan files, and exclusively populated milestone 12.

| Item | Verified result |
| --- | --- |
| Issues #286–#337 | All 52 closed with GitHub reason `not_planned` |
| Closing comments | Exactly one approved comment per issue, with the text below |
| [Milestone 12](https://github.com/angellops/paypal-php-library/milestone/12) | Renamed from `v4.0` to `Archived: original v4.0 plan` and closed |
| Milestone membership | All 52 issues retained; 0 open and 52 closed |
| Milestone description | Explains supersession, archive location, current spec path and future fresh planning |
| Issue titles, bodies and labels | Preserved |
| Repository labels | Preserved |
| Non-target issues and PRs | Unchanged, including merged PRs #284 and #285 |

No issues were deleted or marked as completed implementation. No replacement issues or milestone were created. The cleanup does not approve the replacement spec for implementation or start implementation planning.

## Comment posted to every issue

> Superseded by the replacement v4 SDK modernization specification. The original PRD and implementation plans have been archived. This issue is closed because its plan is obsolete; it does not indicate completed implementation. Replacement implementation issues will be created through fresh planning.

## Verification method

Compared a read-only preflight snapshot against fresh GitHub issue, comment, milestone and label responses after cleanup. Checked the exact mapped issue set, every closure reason and comment, milestone counts/membership, unchanged issue bodies/titles/labels and unchanged non-target issue/PR records. No skipped or failed mutations remained.

The [current spec](../../superpowers/specs/2026-09-04-v4-sdk-modernization-design.md) and [current state](../../handoffs/current-state.md) govern subsequent work. The archived issue-creation script must not be rerun.
