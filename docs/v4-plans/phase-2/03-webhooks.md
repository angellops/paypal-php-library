# Phase 2.3 — Resources\Webhooks

> **SUPERSEDED — do not execute.** This historical plan is replaced by the [v4 replacement specification](../../superpowers/specs/2026-09-04-v4-sdk-modernization-design.md). Its implementation instructions and acceptance criteria are no longer authoritative.

**Phase:** 2 · **Issue:** TBD · **PRD sections:** [§3 Resources/Webhooks](../../PRD.md#proposed-file-structure)

## Context

Webhook CRUD via `/v1/notifications/webhooks` — list/create/show/update/delete the merchant's webhook subscriptions, list available event types. Pure management API; the actual verification of inbound webhooks lives in the separate `WebhookVerifier` resource (Phase 2.4). Standard resource-handler pattern.

## Scope

- `Resources\Webhooks` exposing:
  - `list(): array<WebhookResponse>`
  - `create(string $url, array $eventTypes): WebhookResponse`
  - `show(string $webhookId): WebhookResponse`
  - `update(string $webhookId, array $patches): WebhookResponse`
  - `delete(string $webhookId): void`
  - `listEventTypes(): array<EventTypeResponse>`
- `Responses\WebhookResponse`, `EventTypeResponse` — typed DTOs.
- Unit + sandbox integration tests, doc page.

## Files affected

| Path | Action | Notes |
|---|---|---|
| `src/REST/Resources/Webhooks.php` | NEW | |
| `src/REST/Responses/WebhookResponse.php` | NEW | |
| `src/REST/Responses/EventTypeResponse.php` | NEW | |
| `tests/Unit/REST/Resources/WebhooksTest.php` | NEW | |
| `tests/Integration/REST/WebhooksHappyPathTest.php` | NEW | Sandbox-gated; creates and deletes a test webhook |
| `tests/Fixtures/responses/webhooks-*.json` | NEW | |
| `documentation/rest/webhooks.md` | NEW | |

## Acceptance criteria

- [ ] All 6 methods work against mocked + sandbox responses.
- [ ] Sandbox integration test creates a test webhook, verifies it shows up in list, deletes it — leaves no orphan state.
- [ ] PHPStan level 5 clean. Coverage ≥80%.

## Verification

```bash
composer test -- --filter 'REST\\Resources\\Webhooks'
```

## References

- PayPal docs: https://developer.paypal.com/docs/api/webhooks/v1/
