# Phase 4.2 — samples/rest/ wipe and rebuild

> **SUPERSEDED — do not execute.** This historical plan is replaced by the [v4 replacement specification](../../superpowers/specs/2026-09-04-v4-sdk-modernization-design.md). Its implementation instructions and acceptance criteria are no longer authoritative.

**Phase:** 4 · **Issue:** TBD · **PRD sections:** [§3 file structure / samples](../../PRD.md#proposed-file-structure)

## Context

Same situation as `templates/rest/`: `samples/rest/` on `main` is stale, vendor-SDK-coupled. Wipe it and regenerate ~32 populated, runnable sample files against `REST\Client`. Unlike templates (blank shells), samples are complete and runnable against sandbox.

## Scope

- Delete `samples/rest/` entire subtree.
- Regenerate populated samples mirroring the template structure but with real working code (full payload, real method calls, captured-result echo).
- Each sample file is self-contained: includes config from `samples/config/config-sample.php` (which Phase 4.6 updates with the new config keys), instantiates `REST\Client`, calls one method, prints the resulting DTO via `print_r` and accessor methods.
- Samples are validated by running each against sandbox during Phase 4.2 execution (each one needs to succeed on a known sandbox merchant account or document its dependencies).

## Files affected

| Path | Action | Notes |
|---|---|---|
| `samples/rest/` (entire existing tree) | DELETE | Stale |
| `samples/rest/orders/*.php` | NEW | ~4 runnable samples |
| `samples/rest/payments/*.php` | NEW | ~3 |
| `samples/rest/subscriptions/*.php` | NEW | ~3 |
| `samples/rest/plans/*.php` | NEW | ~2 |
| `samples/rest/catalog-products/*.php` | NEW | ~2 |
| `samples/rest/invoicing/*.php` | NEW | ~5 |
| `samples/rest/payouts/*.php` | NEW | ~2 |
| `samples/rest/disputes/*.php` | NEW | ~3 |
| `samples/rest/vault/*.php` | NEW | ~3 |
| `samples/rest/webhooks/*.php` | NEW | ~3 |
| `samples/rest/identity/*.php` | NEW | ~1 |
| `samples/rest/partner-referrals/*.php` | NEW | ~1 |
| `samples/rest/reports/*.php` | NEW | ~2 |

## Acceptance criteria

- [ ] No sample references `PayPal\Api\*` or vendor-SDK classes.
- [ ] Each sample is syntactically valid PHP.
- [ ] Each sample runs cleanly against the sandbox using credentials from `samples/config/config-sample.php` (when the maintainer fills the real sandbox creds in).
- [ ] Each sample echoes a useful result (order id, capture status, etc.) for verification.

## Verification

```bash
find samples/rest -name '*.php' -exec php -l {} \;
# Manual: pick one sample per resource and run against sandbox.
```

## References

- Upstream: Phase 2 resources, [`06-config-sample-update.md`](06-config-sample-update.md)
