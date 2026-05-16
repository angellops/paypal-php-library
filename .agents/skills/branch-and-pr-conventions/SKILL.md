---
name: branch-and-pr-conventions
description: Repo-specific Git branch naming and GitHub PR conventions for angellops/paypal-php-library. Use when creating a branch, opening a PR, or anytime an agent needs to follow this repo's git/GitHub workflow. Covers branch naming format, base branch selection (v4.0 work targets feat/219-ppcp-integration, not release), PR body sections (BC/compat impact, scope, handoff notes), and how this skill interacts with the git-commit skill.
license: MIT
allowed-tools: Bash, Read, Write, Edit
---

# Branch and PR Conventions — angellops/paypal-php-library

This skill encodes how branches and pull requests work in this repo. Pair it with the `git-commit` skill (which handles commit message format).

## Branch naming

```
{type}/{issue-number}-{slug}
```

- **`type`** — one of: `feat`, `fix`, `chore`, `refactor`, `docs`, `test`
- **`issue-number`** — the GitHub issue this branch closes (or the parent iteration's primary issue if the iteration closes multiple)
- **`slug`** — short kebab-case (3–5 words max), e.g. `rest-orders-adapter`

### Examples

- `feat/42-rest-orders-adapter`
- `fix/87-classic-token-refresh-bug`
- `chore/91-bump-php-floor-to-82`
- `refactor/55-psr0-to-psr4-migration`
- `docs/63-readme-rebrand`
- `test/74-orders-v2-coverage`

### Anti-patterns to avoid

- `feature/...` — use `feat/`, not `feature/`
- `219_ai`, `drew-fix`, `wip-stuff` — no underscore-style, no author names, no "wip"
- Branch names without an issue number — every branch must link to an open issue

## Base branch selection

This repo currently has **two** valid integration targets:

| Branch | Use as base for |
|---|---|
| `release` | The default branch on GitHub. **Only for hotfixes against the released v3.x line.** |
| `feat/219-ppcp-integration` | **All v4.0 iteration work.** Treat this as "main" for v4.0. |

**Default behavior:** if you are doing v4.0 work (which is the active workstream as of writing), base your branch on `feat/219-ppcp-integration` and target it in the PR. If you are doing v3.x maintenance, base on `release` and target `release`.

If unsure, ask the user — do not guess.

## Pull request body — required sections

Every PR must use [.github/pull_request_template.md](../../../.github/pull_request_template.md). The template enforces these sections:

1. **Iteration reference** — link to the `docs/plans/iteration-N.M-*.md` file this PR completes (or note "not part of an iteration" if a one-off)
2. **Summary** — 1–3 bullets on what changed and why
3. **Scope** — explicit in-scope and out-of-scope (deferred) bullets
4. **BC / Compat impact** — breaking changes, deprecations, or behavior changes. "None" is a valid answer; "I don't know" is not.
5. **Test plan** — checklist of how to verify (manual or automated)
6. **Handoff notes** — what the next iteration needs to know. Link to `docs/plans/handoffs/iteration-N.M-handoff.md` if a handoff file was added.
7. **Issues** — one or more `Closes #N` lines for every issue this PR resolves.

## PR title format

```
[Iteration N.M] <conventional-style title>
```

If the PR is not part of an iteration (rare — bootstrap PRs only), drop the `[Iteration N.M]` prefix.

### Examples

- `[Iteration 1.1] feat: bootstrap PHPUnit and add first OrdersV2 DTO tests`
- `[Iteration 2.3] refactor: extract Classic-to-REST adapter for OrderCreate`
- `chore: bootstrap agent workflow scaffolding` *(bootstrap, no iteration ref)*

## Workflow for opening a PR

1. Confirm the branch follows the naming convention above.
2. Confirm the base branch is correct (see table above).
3. Push the branch with `-u` upstream tracking if not already pushed.
4. Run `gh pr create` using a HEREDOC for the body, populating every required section from the template.
5. Apply labels:
   - `phase:N` — which phase this iteration belongs to (e.g., `phase:1`)
   - `iteration:N.M` — which iteration (e.g., `iteration:1.2`)
   - `type:{feat|fix|chore|refactor|docs|test}` — matching the branch type
6. Assign to the active milestone (e.g., `v4.0`) if one exists.
7. Link any issues being closed via `Closes #N` lines in the body.

### Example `gh pr create` invocation

```bash
gh pr create \
  --base feat/219-ppcp-integration \
  --title "[Iteration 1.2] feat: add OrdersV2 DTOs and OrdersClient skeleton" \
  --label "phase:1,iteration:1.2,type:feat" \
  --milestone "v4.0" \
  --body "$(cat <<'EOF'
## Iteration
[docs/plans/iteration-1.2-orders-v2-dtos.md](docs/plans/iteration-1.2-orders-v2-dtos.md)

## Summary
- Adds `OrderCreateRequest` / `OrderCreateResponse` DTOs under `src/angelleye/PayPal/Rest/Orders/V2/`
- Adds `OrdersClient` skeleton with `create()` method
- Adds PHPUnit coverage for both DTOs

## Scope
- **In scope:** OrderCreate request/response DTOs only
- **Out of scope (deferred):** OrderCapture, OrderAuthorize, OrderGet (iteration 1.3+)

## BC / Compat impact
None — purely additive. No changes to existing Classic code.

## Test plan
- [x] `vendor/bin/phpunit tests/Rest/Orders/V2/` passes
- [x] No regressions in `vendor/bin/phpunit` full suite

## Handoff notes
See [docs/plans/handoffs/iteration-1.2-handoff.md](docs/plans/handoffs/iteration-1.2-handoff.md) — documents the DTO base class extension point that iteration 1.3 will use for OrderCapture.

## Issues
Closes #42
Closes #43
EOF
)"
```

## Interaction with the `git-commit` skill

The `git-commit` skill handles **commit messages** (Conventional Commit format). This skill handles **branch names and PR descriptions**. Both can be active in the same workflow:

1. Use `git-commit` to stage and create the commit with a properly-formatted message.
2. Use this skill's conventions when pushing the branch and opening the PR.

## When this skill applies

Invoke whenever the user (or another skill/workflow) is about to:

- Create a new git branch
- Open a pull request
- Decide what base branch to target
- Apply labels/milestones to a PR or issue
- Write a PR description from scratch

If the user explicitly tells you to deviate from these conventions for a specific PR (e.g., "don't worry about the iteration label, this is a hotfix"), the user's instruction wins — but flag the deviation in your response so it's intentional.
