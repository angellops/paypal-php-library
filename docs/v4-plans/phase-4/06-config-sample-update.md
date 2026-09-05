# Phase 4.6 — samples/config/config-sample.php update

> **SUPERSEDED — do not execute.** This historical plan is replaced by the [v4 replacement specification](../../superpowers/specs/2026-09-04-v4-sdk-modernization-design.md). Its implementation instructions and acceptance criteria are no longer authoritative.

**Phase:** 4 · **Issue:** TBD · **PRD sections:** [§3 Integration Points / Merchant config](../../PRD.md#integration-points)

## Context

`samples/config/config-sample.php` is the single source merchants copy when bootstrapping. It needs all the new v4.0 config keys documented inline. Existing Classic keys stay; new REST keys are added with explanatory comments.

## Scope

- Add `ClientID` and `ClientSecret` keys with comments pointing at developer.paypal.com.
- Add `upgrade_from_classic` (boolean, default false) with a comment explaining the auto-fallback behavior.
- Add `TokenStore` (string: `'memory'` | `'filesystem'` | `'psr16'`, default `'memory'`).
- Add `TokenStorePath` (filesystem path, only used when `TokenStore === 'filesystem'`).
- Add `TokenStoreTTL` (int seconds, default 9 * 60 to safely cache OAuth tokens before PayPal's typical 32400-second expiry).
- Add `RESTLogPath` (filesystem path, optional).
- Add `on_rest_error` (callable, optional — invoked when a REST request errors).
- Inline comment block at the top explaining: PartnerAttributionId is NOT a config key (it's hardcoded via `Support\PartnerAttribution::VALUE`), and there is no `classic_methods_passthrough` key (auto-fallback is implicit).
- Add a short usage block showing minimal vs. full config examples.

## Files affected

| Path | Action | Notes |
|---|---|---|
| `samples/config/config-sample.php` | EDIT | Add new keys + inline docs |

## Acceptance criteria

- [ ] All new config keys present with explanatory comments.
- [ ] Inline comment explicitly states PartnerAttributionId is NOT configurable.
- [ ] Inline comment explicitly states there is no `classic_methods_passthrough` key.
- [ ] File is syntactically valid PHP.
- [ ] Existing Classic keys are preserved unchanged.

## Verification

```bash
php -l samples/config/config-sample.php
grep -n 'ClientID\|ClientSecret\|upgrade_from_classic\|TokenStore\|RESTLogPath\|on_rest_error' samples/config/config-sample.php
```

## References

- PRD: [§3 Integration Points / Merchant config](../../PRD.md#integration-points)
- Memory: [BN code is hardcoded, not config](/home/angellops/.claude/projects/-home-angellops-projects-paypal-sdk-php/memory/feedback_partner_attribution_id.md)
