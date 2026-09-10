# Upgrade Guide: 0.9.x -> 0.10.0

This guide lists the required changes to safely migrate a project from Trader `0.9.x` to `0.10.0`.

## 1. Preflight checks

Create a database backup, run the current test suite, and search application code before updating Trader:

```bash
rg --glob '*.php' --glob '!vendor/**' "OrderVatSnapshot|WithOrderVatSnapshot|AdjustOrderVatSnapshot|VatSnapshot(NotCalculated|MismatchException)|applyVatSnapshot|invalidateVatSnapshot|hasUpToDateVatSnapshot|getVatRoundingStrategy|VatRoundingStrategy|areItemDiscountsCalculatedExcludingVat|setCalculateExcludingVat|getForFilter|findForFilter" .
```

Every match must be reviewed. Do not complete the upgrade while old snapshot API references remain.

### Hanolux prerequisite

Hanolux uses `AdjustOrderVatSnapshot` in its old VAT migration command. Update that command to
`AdjustOrderPricingSnapshot` before updating Hanolux to Trader `0.10`. Treat a remaining
`AdjustOrderVatSnapshot` reference as a blocking error, even though Trader temporarily contains a deprecated wrapper.

## 2. Update the package

Change the Trader constraint to `^0.10`, then run:

```bash
composer update thinktomorrow/trader
```

## 3. Run the database migrations

Run the package migrations on a copy of production data first:

```bash
php artisan migrate
```

The migrations add:

- `tax_mode` to `trader_shipping_profile_tariffs` and `trader_payment_methods`;
- `cost_incl`, `discount_incl`, `total_incl`, and `cost_tax_mode` to `trader_order_shipping` and `trader_order_payment`;
- `tax_mode` to `trader_order_discounts`;
- `pricing_fingerprint` to `trader_orders`.

Existing tariffs, payment methods, shipping, and payment records remain `exclusive`: this preserves their previous
meaning. Existing order discounts with a non-null `total_incl` are backfilled as `inclusive`. Do not bulk-convert
existing prices to `inclusive`.

If `trader_orders.vat_calculation_fingerprint` exists, the project used an unreleased intermediate Trader version.
If both `vat_calculation_fingerprint` and `pricing_fingerprint` exist, stop and reconcile their data manually before
continuing; do not automatically drop either column.

## 4. Update config and custom TraderConfig implementations

In published `config/trader.php`:

- remove `vat_rounding_strategy`;
- add `'does_tariff_input_includes_vat' => true` or `false` to match how new tariff rates are entered;
- keep `calculate_item_discounts_excluding_vat`; it now determines the item discount `TaxMode`.

For every custom `Thinktomorrow\Trader\TraderConfig` implementation:

```php
use Thinktomorrow\Trader\Domain\Common\Price\TaxMode;

public function doesTariffInputIncludeVat(): bool;
public function getItemDiscountTaxMode(): TaxMode;
```

Replace `areItemDiscountsCalculatedExcludingVat(): bool` with `getItemDiscountTaxMode(): TaxMode`. Remove
`getVatRoundingStrategy()` and all use of the removed `VatRoundingStrategy`. VAT now always rounds on the full line
amount.

## 5. Rename the pricing snapshot API

This is an intentional API break. Apply all relevant replacements:

| Before | After |
| --- | --- |
| `OrderVatSnapshot` | `OrderPricingSnapshot` |
| `WithOrderVatSnapshot` | `WithOrderPricingSnapshot` |
| `AdjustOrderVatSnapshot` | `AdjustOrderPricingSnapshot` |
| `VatSnapshotNotCalculated` | `PricingSnapshotNotCalculated` |
| `VatSnapshotMismatchException` | `PricingSnapshotMismatchException` |
| `applyVatSnapshot()` | `applyPricingSnapshot()` |
| `invalidateVatSnapshot()` | `invalidatePricingSnapshot()` |
| `hasUpToDateVatSnapshot()` | `hasUpToDatePricingSnapshot()` |

`OrderPricingSnapshot` lives in
`Thinktomorrow\Trader\Domain\Model\Order\Snapshot\OrderPricingSnapshot`. Also update constructor injection, container
bindings, imports, mocks, tests, and custom order aggregates. Use `findPricingSnapshot()` when the snapshot itself is
needed.

Trader provides `trader:recalculate-order-pricing` for controlled recalculation of one order:

```bash
php artisan trader:recalculate-order-pricing <order-uuid> --dry-run
php artisan trader:recalculate-order-pricing <order-uuid>
```

Always dry-run first. Frozen orders without a pricing snapshot are rejected, and frozen totals are never silently
changed.

## 6. Update customized contracts and factories

Only apply these steps where the project customizes or constructs the affected types:

- Implement `getTaxMode(): TaxMode` and `getAuthoritativeAmount(): Money` on custom `ItemPrice`,
  `ItemDiscountPrice`, `ServicePrice`, and `DiscountPrice` implementations.
- Pass `VatAllocator` as the fourth argument to custom calls to `LineDiscount::fromMappedData()` and
  `OrderDiscount::fromMappedData()`.
- Replace `LineDiscount::setCalculateExcludingVat(bool)` with `setCalculationTaxMode(TaxMode)`.
- Inject `OrderServicePriceResolver` when manually constructing `UpdateShippingProfileOnOrder` or
  `UpdatePaymentMethodOnOrder`. Laravel autowiring already handles package-managed instances.
- Move calls to `TaxonomyRepository::getForFilter()` and `findForFilter()` to `TaxonomyItemRepository`. Custom
  repositories must bind both contracts where needed.
- Update custom merchant order, payment, and shipping read models or mocks for the new state-reader contracts.

## 7. Set tax authority explicitly for new prices

Tariff, payment method, and fixed order discount amounts can now be `inclusive` or `exclusive` VAT authoritative.
Pass the mode explicitly for business-critical input:

- `CreatePaymentMethod` and `UpdatePaymentMethod`: use the final `taxMode` argument;
- `CreateTariff` and `UpdateTariff`: use the final `taxMode` argument;
- fixed promo discounts: set `data.tax_mode`.

Use `inclusive` for merchant-entered gross amounts and `exclusive` for net amounts. Payment method creation and fixed
discounts default to `exclusive`; tariff creation falls back to `does_tariff_input_includes_vat`. An omitted mode on an
update preserves the persisted mode.

## 8. Verify behavior

Pricing can differ by cents because allocation now uses an integer-safe largest-remainder algorithm, VAT rounds on the
full line amount, combined promo accumulation was fixed, and order discounts now also apply to payment costs. Cart
refresh also revalidates selected payment methods and removes missing or unavailable ones.

Before release, verify:

- inclusive and exclusive tariffs, payment methods, and fixed discounts;
- mixed VAT rates, VAT exemption, line discounts, shipping discounts, and payment discounts;
- refresh and checkout of legacy carts;
- invoices and exports against representative production orders;
- confirmed and paid orders retain their frozen historical totals;
- the full project test suite and static analysis pass.

Finally, rerun the preflight search from step 1. It must return no obsolete API usage, except in documentation or
explicit compatibility tests.
