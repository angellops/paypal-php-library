# Phase 2.4 — Resources\WebhookVerifier

**Phase:** 2 · **Issue:** TBD · **PRD sections:** [§3 Resources/WebhookVerifier](../../PRD.md#proposed-file-structure), [§3 Security & Privacy](../../PRD.md#security--privacy)

## Context

Verifies that an inbound webhook POST actually came from PayPal. **API-only strategy** (per brainstorming decision): calls PayPal's `/v1/notifications/verify-webhook-signature` with the inbound headers + body + webhook ID. Returns boolean. Offline cryptographic verification (cert chain + signature) is deferred to v4.1+. The merchant owns the HTTP endpoint that receives PayPal's POST; this helper just answers "is this real?"

## Scope

- `Resources\WebhookVerifier extends BaseResource` exposing a single method:
  - `verify(array $headers, string $rawBody, string $webhookId): bool`
- Method builds the verification payload from the inbound headers (`paypal-transmission-id`, `paypal-transmission-time`, `paypal-transmission-sig`, `paypal-cert-url`, `paypal-auth-algo`), POSTs to PayPal's verify endpoint, returns `true` if the response's `verification_status === 'SUCCESS'`.
- Header lookup is case-insensitive (the merchant's framework may have normalized).
- Doc page `documentation/rest/webhook-verifier.md` (the broader `documentation/webhooks.md` lives in Phase 5).

## Files affected

| Path | Action | Notes |
|---|---|---|
| `src/REST/Resources/WebhookVerifier.php` | NEW | Single `verify()` method |
| `tests/Unit/REST/Resources/WebhookVerifierTest.php` | NEW | Mocked Transport; verifies SUCCESS + FAILURE paths |
| `tests/Integration/REST/WebhookVerifierTest.php` | NEW | Sandbox-gated; uses PayPal's developer dashboard "simulate event" capability |
| `tests/Fixtures/responses/webhook-verify-success.json` | NEW | |
| `tests/Fixtures/responses/webhook-verify-failure.json` | NEW | |

## Acceptance criteria

- [ ] `verify()` returns `true` when PayPal's API returns `{"verification_status": "SUCCESS"}`.
- [ ] `verify()` returns `false` when PayPal's API returns `{"verification_status": "FAILURE"}`.
- [ ] Header lookup is case-insensitive (test with `Paypal-Transmission-Id` and `PAYPAL-TRANSMISSION-ID`).
- [ ] Missing required headers throws `ValidationException` with a clear message.
- [ ] PHPStan level 5 clean. Coverage ≥80%.

## Verification

```bash
composer test -- --filter WebhookVerifier
```

## References

- PRD: brainstorming decision pins this to API-only for v4.0
- PayPal docs: https://developer.paypal.com/docs/api/webhooks/v1/#verify-webhook-signature
