# AGENTS.md

Single source of truth for any AI agent working in this repo. `CLAUDE.md` imports this file; `.codex/` and similar can do the same.

## Project at a glance

- **Repo:** [angellops/paypal-php-library](https://github.com/angellops/paypal-php-library)
- **Composer package:** `angelleye/paypal-php-library` (will become `wekoodo/paypal-php-library` at v4.0 publish)
- **PHP namespace:** `angelleye\PayPal`
- **Current stable:** v3.0.5
- **In progress:** v4.0 modernization — adds REST + Classic-to-REST upgrade adapter; v5.0 will drop Classic. See [docs/PRD.md](docs/PRD.md).
- **Brand history:** Angell EYE → angellops → Wekoodo (v4.0 is the rebrand release; PHP namespace preserved for BC)

## Tech stack

- **PHP:** `>=8.0` currently; v4.0 raises the floor to `^8.1` (early Phase 0 iteration)
- **Composer autoload:** PSR-0 today; v4.0 adopts **dual PSR-0 / PSR-4** — new code under `src/REST/`, `src/Legacy/`, `src/Support/` is PSR-4 at the namespace prefix `angelleye\PayPal\{REST,Legacy,Support}\`; the existing classes (`PayPal`, `PayFlow`, `Adaptive`, `Financing`) stay PSR-0 in their current locations under `src/angelleye/PayPal/` to preserve BC for merchants' `use` statements. Full PSR-4 migration of the legacy classes is deferred to v5.0 (when Classic itself is dropped).
- **Standard for new code:** PSR-12 (legacy code remains PSR-0-laid-out)
- **Tests:** none configured yet — v4.0 introduces **PHPUnit** as an early Phase 0 iteration
- **Lint / static analysis:** none configured yet — tool choice (PHP-CS-Fixer / PHPStan / Psalm) deferred; not blocking for v4.0
- **Local dev:** DDEV (PHP 8.2, Apache, MariaDB) — see [.ddev/config.yaml](.ddev/config.yaml)
- **Runtime dep:** `ext-curl` only

## Git / GitHub conventions

- **Default branch on GitHub:** `release`
- **v4.0 integration branch:** `feat/219-ppcp-integration` — treat this as "main" for v4.0 work; all iteration branches merge here, not into `release`
- **Branch naming:** `{type}/{issue-number}-{slug}` (e.g., `feat/42-rest-orders-adapter`)
- **Branch types:** `feat`, `fix`, `chore`, `refactor`, `docs`, `test`
- **Commits:** Conventional Commits — handled by [.agents/skills/git-commit/](.agents/skills/git-commit/)
- **PR template:** [.github/pull_request_template.md](.github/pull_request_template.md)
- **Detailed git/PR rules:** [.agents/skills/branch-and-pr-conventions/](.agents/skills/branch-and-pr-conventions/)

## How work is structured

Two-level hierarchy. Phases are workstreams; iterations are the executable atoms.

- **PRD** — [docs/PRD.md](docs/PRD.md) — vision and scope. **Not auto-loaded.** Read on demand during planning, not during execution.
- **Workflow guide** — [docs/plans/README.md](docs/plans/README.md) — explains the phase + iteration system in detail.
- **Roadmap** — [docs/plans/ROADMAP.md](docs/plans/ROADMAP.md) — phase and iteration tracker. Mirrors GitHub milestone/issues.
- **Iteration specs** — `docs/plans/iteration-N.M-<slug>.md` — the executable plan for one session / one PR.
- **Handoffs** — `docs/plans/handoffs/iteration-N.M-handoff.md` — the delta written by an iteration when it finishes, consumed only by iterations that explicitly list it as a dependency.

**Context-economy invariant:** an agent executing iteration `N.M` reads its own iteration spec, the handoff files that spec explicitly lists as dependencies, and the code. It does **not** read the PRD, the roadmap, or unrelated iteration/handoff files.

## Project-specific gotchas

- **BN code / Partner-Attribution-Id** is **hardcoded** to `WekoodoLLC_Ecom` in code, never config — intentionally invisible to merchants.
- **AWS telemetry** has been decommissioned AWS-side (endpoint + key killed). Code-side cleanup remains as part of v4.0.
- **Base for v4.0 work is `feat/219-ppcp-integration`, NOT `release`.** Any iteration PR opened against `release` while v4.0 is in progress is wrong.

## Project skills

Available under [.agents/skills/](.agents/skills/) (symlinked to `.claude/skills/`):

- [paypal](.agents/skills/paypal/) — PayPal API/SDK guidance
- [php-pro](.agents/skills/php-pro/) — modern PHP 8.x idioms
- [prd](.agents/skills/prd/) — PRD authoring
- [git-commit](.agents/skills/git-commit/) — Conventional Commit message generation
- [branch-and-pr-conventions](.agents/skills/branch-and-pr-conventions/) — repo-specific git/PR rules (branch naming, base branch, PR body sections)
- [stripe-best-practices](.agents/skills/stripe-best-practices/) — Stripe reference (for parity/comparison only)
- [to-issues](.agents/skills/to-issues/) — ad-hoc decomposition of a plan/spec into vertical-slice GitHub issues (Matt Pocock, from skills.sh). **Not used by `/plan-phase`** — the v4.0 phase-planning workflow ships one issue per iteration (no further decomposition). Available for one-off decomposition work outside the iteration framework.
