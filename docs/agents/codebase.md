# Codebase reference

Use this map to select source and callers. Read the actual implementation before changing a public contract; names alone do not establish API equivalence.

## Integration surfaces

Paths below are relative to the repository root.

| Surface | Starting files | What to inspect |
| --- | --- | --- |
| Classic merchant NVP | `src/angelleye/PayPal/PayPal.php` | Grouped arrays such as `SECFields`, `Payments`, `DECPFields`; scalar token/profile methods; derived response arrays |
| Adaptive and older service APIs | `src/angelleye/PayPal/Adaptive.php` | XML service-specific methods, including older Invoice APIs; separate from REST invoicing |
| REST invoice model | `src/angelleye/PayPal/InvoicingClass.php` | Project-owned fluent/static methods, vendor inheritance, `/v2/invoicing` calls |
| REST invoice facades | `src/angelleye/PayPal/rest/invoice/InvoiceAPI.php`, `InvoiceAPIv2.php` | Separate wrappers, request shapes, result envelopes, third-party token handling |
| Existing REST infrastructure | `src/angelleye/PayPal/RestClass.php`, `CheckoutOrdersClass.php`, `CustomerDisputesClass.php`, `PayPalSyncClass.php`, `rest/` | Class-specific vendor/API dependencies; inspect individually before replacement |
| Payflow | `src/angelleye/PayPal/PayFlow.php` | Independent gateway protocol and inherited helper dependencies |
| Other public classes | `src/angelleye/PayPal/ReferencedPayoutsClass.php`, `EventTypesClass.php`, `Financing.php` | Existing callers and original contract; avoid blanket deletion |

REST invoicing is a first-class compatibility surface. Do not treat `Adaptive.php` invoice methods as the complete inventory of existing invoicing users. Similarly, a REST method named like a Classic method does not establish equal fields, consent, status or historical-ID semantics.

## Callers, examples and documentation

| Location | Use |
| --- | --- |
| [templates/](../../templates/) | Parameter-array examples for individual API calls |
| [samples/](../../samples/) | Usage examples; start configuration inspection at `samples/config/config-sample.php` |
| [demo/classic/](../../demo/classic/) | Multi-request checkout and other legacy integration flows |
| [demo/rest/](../../demo/rest/) | Existing REST browser/server call sequences |
| [documentation/](../../documentation/) | Legacy HTML user documentation |
| [README.md](../../README.md), [CHANGELOG.md](../../CHANGELOG.md) | Existing user-facing installation/history; not proof that v4 has shipped |

## Runtime and tooling

[composer.json](../../composer.json) is authoritative for currently declared package name, PHP constraint, extensions, dependencies and autoloading. [autoload.php](../../autoload.php) is the manual loader. Read both for packaging changes.

At context setup, the manifest still names `angelleye/paypal-php-library`, declares PHP `>=5.3.0`, uses PSR-0 and requires `paypal/rest-api-sdk-php`. The v4 design instead targets `wekoodo/paypal-php-library`, PHP `^8.3` and an SDK-owned REST core. These are deliberate current-versus-target differences, not completed migrations. Recheck the manifest as implementation progresses.

Local `.ddev/config.yaml`, if present, describes a developer environment, not a portable release requirement. At setup it selected PHP 8.2 and was not tracked. `vendor/` and `composer.lock` are ignored under the current [.gitignore](../../.gitignore); their local presence does not prove reproducible package requirements. Do not commit them incidentally.

There is no tracked PHPUnit configuration, PHPStan configuration, test suite or CI workflow at context setup. The v4 spec describes future acceptance requirements. Discover actual commands before reporting test results; see [verification guidance](workflow.md#verification).
