# Phase 2.11 — Resources\Vault

**Phase:** 2 · **Issue:** TBD · **PRD sections:** [§3 Resources/Vault](../../PRD.md#proposed-file-structure)

## Context

Vault v3 (`/v3/vault/*`) — store payment methods (cards, PayPal accounts, etc.) for future use. Includes setup tokens (used during the first transaction to vault the method) and payment tokens (the persistent vaulted method). Phase 3.8's `CreateBillingAgreementMapper` writes to this resource.

## Scope

- `Resources\Vault` exposing:
  - `createSetupToken(array $body, ?RequestOptions $opts = null): SetupTokenResponse`
  - `showSetupToken(string $setupTokenId): SetupTokenResponse`
  - `createPaymentToken(array $body, ?RequestOptions $opts = null): VaultedTokenResponse`
  - `showPaymentToken(string $paymentTokenId): VaultedTokenResponse`
  - `listPaymentTokens(string $customerId): array<VaultedTokenResponse>`
  - `deletePaymentToken(string $paymentTokenId): void`
- `Responses\SetupTokenResponse`, `VaultedTokenResponse`.

## Files affected

| Path | Action | Notes |
|---|---|---|
| `src/REST/Resources/Vault.php` | NEW | |
| `src/REST/Responses/SetupTokenResponse.php` | NEW | |
| `src/REST/Responses/VaultedTokenResponse.php` | NEW | |
| `tests/Unit/REST/Resources/VaultTest.php` | NEW | |
| `tests/Integration/REST/VaultHappyPathTest.php` | NEW | Sandbox-gated |
| `tests/Fixtures/responses/vault-*.json` | NEW | |
| `documentation/rest/vault.md` | NEW | |

## Acceptance criteria

- [ ] All 6 methods work against mocked + sandbox responses.
- [ ] PHPStan level 5 clean. Coverage ≥80%.

## References

- PayPal docs: https://developer.paypal.com/docs/api/payment-tokens/v3/
