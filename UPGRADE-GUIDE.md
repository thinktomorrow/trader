# Upgrade Guide: 0.8.x -> 0.9.0

This guide lists the required changes to migrate a project from Trader `0.8.x` to `0.9.0`.

## 1) Update package

```bash
composer update thinktomorrow/trader
```

## 2) Database updates (required)

`0.9.0` introduces variant keys and expects storage in `trader_product_keys`.

If your project already ran older Trader migrations, add a new migration in your project for this table:

```php
Schema::create('trader_product_keys', function (Blueprint $table) {
    $table->string('key', 191);
    $table->char('product_id', 36)->index();
    $table->char('variant_id', 36)->index();
    $table->string('locale', 10);

    $table->primary(['locale', 'key']);
    $table->unique(['locale', 'variant_id']);

    $table->foreign('product_id')->references('product_id')->on('trader_products')->onDelete('cascade');
    $table->foreign('variant_id')->references('variant_id')->on('trader_product_variants')->onDelete('cascade');
});
```

Also align `trader_taxa_keys` constraints with the new base migration:

- `key` length: `191`
- `locale` length: `10`
- unique index on `['locale', 'taxon_id']`

Apply these changes only after checking existing data for duplicates/truncation risk.

## 3) Config updates (required)

Add these keys to `config/trader.php`:

```php
'calculate_item_discounts_excluding_vat' => false,
'show_variants_in_grid_by_default' => false,
```

## 4) TraderConfig updates (required)

If your project provides a custom `TraderConfig` implementation, implement:

- `areItemDiscountsCalculatedExcludingVat(): bool`
- `showVariantsInGridByDefault(): bool`

## 5) Contract/interface updates (required if customized)

Update any custom classes implementing these contracts:

- `Thinktomorrow\Trader\Application\Cart\Read\CartLine`
  - `getDiscountPercentage(): float` (was `int`)
  - add `getFormattedDiscountPercentage(): float`
- `Thinktomorrow\Trader\Application\Promo\LinePromo\LineDiscount`
  - add `setCalculateExcludingVat(bool $calculateExcludingVat): void`
- `Thinktomorrow\Trader\Application\Product\ProductDetail\ProductDetailRepository`
  - add `findProductDetailByKey(Locale $locale, string $variantKey, bool $allowOffline = false): ProductDetail`
- `Thinktomorrow\Trader\Application\Product\ProductDetail\ProductDetail`
  - update `fromMappedData(...)` signature
  - add `getKey()`, `getPersonalisations()`, `getProductData()`
- `Thinktomorrow\Trader\Application\Product\Grid\GridItem`
  - update `fromMappedData(...)` signature
- `Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderLine`
  - add `getVariants(): array`
- `Thinktomorrow\Trader\Application\Cart\ShippingProfile\ShippingProfileForCart`
  - add `getData(?string $key = null, $default = null): mixed`

## 6) Variant URL/key behavior

Product detail lookup can now resolve by localized variant key via:

- `findProductDetailByKey(Locale $locale, string $variantKey, bool $allowOffline = false)`

If your storefront uses custom variant URLs/slugs, verify route-model behavior and generated URLs.

## 7) Taxon key locale fallback behavior

`DefaultProductTaxonItem::getKey($locale)` now returns `null` when no key exists for that locale.

If your UI relied on implicit fallback, add explicit fallback logic in your app layer.

## 8) Discount/VAT behavior review

The new setting `calculate_item_discounts_excluding_vat` controls whether item discounts are calculated on excl. VAT values.

Review this with your pricing model:

- B2C shops usually keep this `false`
- B2B shops may want this `true`

## 9) Deprecated API

- `VariantForCartRepository::findVariantForCart()` is deprecated.
- Use `ProductDetailRepository::findProductDetail()` for cart/product detail enrichment.

## 10) Recommended verification checklist

- Run your migrations on staging data first.
- Run your full test suite.
- Verify cart and checkout totals for discount/VAT scenarios.
- Verify product detail routes using variant keys.
- Verify taxon key behavior for missing locales.
- Verify merchant order line variant metadata rendering.
