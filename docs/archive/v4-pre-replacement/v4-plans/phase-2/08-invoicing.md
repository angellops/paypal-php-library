# Phase 2.8 — Resources\Invoicing

> **SUPERSEDED — do not execute.** This historical plan is replaced by the [v4 replacement specification](../../../../superpowers/specs/2026-09-04-v4-sdk-modernization-design.md). Its implementation instructions and acceptance criteria are no longer authoritative.

**Phase:** 2 · **Issue:** TBD · **PRD sections:** [§3 Resources/Invoicing](../../PRD.md#proposed-file-structure)

## Context

Invoicing v2 (`/v2/invoicing/*`) covers invoices, invoice templates, and QR-code generation. Replaces Classic's `CreateInvoice` / `SendInvoice` / etc. methods. The mappers in Phase 3.9 depend on this resource.

## Scope

- `Resources\Invoicing` exposing:
  - `createDraft(array $body, ?RequestOptions $opts = null): InvoiceResponse`
  - `list(?int $page = null, ?int $pageSize = null): array<InvoiceResponse>`
  - `show(string $invoiceId): InvoiceResponse`
  - `update(string $invoiceId, array $body): InvoiceResponse`
  - `delete(string $invoiceId): void`  // drafts only
  - `send(string $invoiceId, ?bool $sendToRecipient = true, ?bool $sendToInvoicer = false): void`
  - `remind(string $invoiceId, ?string $subject = null, ?string $note = null): void`
  - `cancel(string $invoiceId, ?string $subject = null, ?string $note = null): void`
  - `recordPayment(string $invoiceId, array $body): array`
  - `recordRefund(string $invoiceId, array $body): array`
  - `generateQrCode(string $invoiceId, ?int $width = 500, ?int $height = 500): string`  // returns PNG bytes
  - `listTemplates(): array<InvoiceTemplateResponse>`
  - `createTemplate(array $body): InvoiceTemplateResponse`
  - `updateTemplate(string $templateId, array $body): InvoiceTemplateResponse`
  - `deleteTemplate(string $templateId): void`
- `Responses\InvoiceResponse`, `InvoiceTemplateResponse`.

## Files affected

| Path | Action | Notes |
|---|---|---|
| `src/REST/Resources/Invoicing.php` | NEW | Largest resource by method count |
| `src/REST/Responses/InvoiceResponse.php` | NEW | |
| `src/REST/Responses/InvoiceTemplateResponse.php` | NEW | |
| `tests/Unit/REST/Resources/InvoicingTest.php` | NEW | |
| `tests/Integration/REST/InvoicingHappyPathTest.php` | NEW | Sandbox-gated; creates draft, sends, records refund, deletes |
| `tests/Fixtures/responses/invoicing-*.json` | NEW | |
| `documentation/rest/invoicing.md` | NEW | |

## Acceptance criteria

- [ ] All 15 methods work against mocked + sandbox responses.
- [ ] `generateQrCode()` returns binary PNG bytes (verify magic header `\x89PNG`).
- [ ] Sandbox integration walks the full invoice lifecycle.
- [ ] PHPStan level 5 clean. Coverage ≥80%.

## References

- PayPal docs: https://developer.paypal.com/docs/api/invoicing/v2/
