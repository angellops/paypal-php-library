# Branch and release policy

Use this conservative policy for the major v4 rewrite. It keeps the current development branch compatible with v3 while implementation proceeds separately. The user's later branch instructions take precedence.

## Before v4 general availability

| Branch | Purpose | Pull request target |
| --- | --- | --- |
| `main` | Current v3-compatible line; shared documentation, context and compatible maintenance | PRs for documentation or v3-compatible fixes target `main` |
| `v4` | Integration branch for the replacement v4 design and implementation | v4 feature/fix branches target `v4` |
| Short-lived topic branches | One scoped change based on its intended target | Merge to the branch it was based on after appropriate verification/review |

The v4 integration branch begins at the merged documentation/context baseline. Do not use older `dev`, `release`, `release-rest` or experimental branches as the v4 base merely because they exist. There is no additional general-purpose `dev` branch in this workflow.

Shared docs and compatible fixes that land on `main` should be brought into `v4` through reviewed forward merges. Before starting work, check the target branch, the current spec's review status and the task's authorization. A branch existing is not approval to begin implementation.

## Release boundaries

Published tags identify releases. Do not move or recreate existing release tags. A branch merge does not itself authorize a new tag, GitHub release, Packagist change or package rename.

Composer stable version constraints resolve tagged releases; branch constraints such as `dev-main` follow development branches. Keeping incompatible code on `v4` protects `main` consumers during development as well as making the release boundary explicit. Version-like branch names have Composer-specific development-version syntax; consult [Composer's versions documentation](https://getcomposer.org/doc/articles/versions.md#branches) when documenting early-adopter installation.

Before the approved v4 GA merge, preserve the then-current v3-compatible line as `3.x` for the spec's security-maintenance window. Merge the verified `v4` branch into `main` through a release PR, then perform the separately authorized v4 tagging/package cutover. After GA, v4 work targets `main`; v3 maintenance targets `3.x`. Neither a `3.x` branch nor a v4 release needs to be created during the documentation integration.

## Verification

For documentation integration, verify no change to SDK source, the Composer manifest, autoloading or examples, and run context/link/whitespace checks. For implementation and release PRs, use the applicable plan and spec acceptance evidence. Check the actual GitHub merge rules and required checks; do not bypass a rejection or invent checks that the repository does not have.
