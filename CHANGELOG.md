# Changelog

Important changes will be notified in this file

## Unreleased

## 2026-04-29 - 0.9.3

- Add CustomerCreated event

## 2026-04-16 - 0.9.2

- Fixed: pivot order of product/variant taxa

## 2026-04-14 - 0.9.1

- Fixed: prevent taxa rows with `NULL` pivot data from being dropped during repository hydration in
  `MysqlProductRepository` and `MysqlVariantRepository` by using null-safe concatenation in taxa select statements.
- Fixed: taxon findMany ordering for UUID ids
- Fixed: Issue where UpdateShippingAddress and UpdateBillingAddress removed existing data.
- Fixed: static analysis errors level 1
- Added: `VatRoundingStrategy::getDefault()` and `VatRoundingStrategy::fromStringOrDefault()` to centralize fallback
  behavior.
- Added: Extra Snapshot validation before saving order
- Added: Specific VatSnapshotMismatchException in case vat snapshot of an order is invalid.
- Added: TaxonomyItem::getData() method
- Added: Laravel boost guideline
- Added: laravel/pint as codestyle + ran pint
- Changed: Default VAT rounding strategy is now line-based.
- Changed: Fallback VAT rounding behavior now consistently follows the centralized default strategy.

## 2026-03-17 - 0.9.0

### Breaking changes

- Added methods to `TraderConfig`: `areItemDiscountsCalculatedExcludingVat()` and `showVariantsInGridByDefault()`.
- Changed `CartLine::getDiscountPercentage()` return type from `int` to `float`.
- Added `CartLine::getFormattedDiscountPercentage(): float`.
- Added `LineDiscount::setCalculateExcludingVat(bool $calculateExcludingVat): void`.
- Added
  `ProductDetailRepository::findProductDetailByKey(Locale $locale, string $variantKey, bool $allowOffline = false): ProductDetail`.
- Changed `ProductDetail::fromMappedData()` signature to accept taxa, variant keys, and personalisations.
- Added methods to `ProductDetail`: `getKey()`, `getPersonalisations()`, and `getProductData()`.
- Changed `GridItem::fromMappedData()` signature to include variant keys.
- Added `MerchantOrderLine::getVariants(): array`.
- Added `ShippingProfileForCart::getData(?string $key = null, $default = null): mixed`.
- Changed `DefaultProductTaxonItem::getKey($locale)` behavior: returns `null` when the requested locale key is missing.

### Added

- Added variant key support in the domain model:
    - `VariantKey`, `VariantKeyId`, and `HasVariantKeys`
    - events `VariantKeyCreated` and `VariantKeyUpdated`
    - command `UpdateVariantKeys`
    - `ProductApplication::updateVariantKeys()`
- Added persistence and hydration support for variant keys in MySQL and in-memory repositories.
- Added product detail lookup by variant key with `findProductDetailByKey()`.
- Added personalisations and variant keys to product detail mapping.
- Added config option: `calculate_item_discounts_excluding_vat`.
- Added config option: `show_variants_in_grid_by_default`.
- Added taxa metadata to cart lines and merchant order lines for richer rendering.

### Changed

- Replaced cart variant enrichment flow with `ProductDetailRepository` (deprecated
  `VariantForCartRepository::findVariantForCart()`).
- Changed discount percentage behavior to preserve decimal precision.
- Changed VAT/rounding internals to improve total consistency.
- Changed taxon key sorting behavior to sort by locale.
- Changed taxon key fallback behavior to avoid implicit fallback for missing locale keys.
- Changed CI matrix to Laravel 11/12 and PHP 8.4/8.5; tests now run as split Unit/Acceptance/Infrastructure jobs.

### Migration and schema notes

- Added table in base migration: `trader_product_keys`.
- Updated `trader_taxa_keys` constraints in base migration (`key` length 191, `locale` length 10, unique on
  locale+taxon).
- If your project already ran prior migrations, create forward migrations in your app for equivalent schema updates.

### Deprecated

- `VariantForCartRepository::findVariantForCart()` is deprecated; use `ProductDetailRepository::findProductDetail()`.

### Fixed

- Fixed VAT allocation and cash rounding consistency in edge rounding scenarios.
- Fixed repository and test-driver parity in several product/taxon/stock paths.

## 2026-02-02 - 0.8.1

- Fixed: VAT snapshot adjustment while fetching existing carts

## 2026-02-02 - 0.8.0

- Fixed: product/variant title sorting logic
- Changed: now requires PHP 8.4 and Laravel 12

### Introducing Taxonomy & Taxon setup

A Taxon now belongs to a Taxonomy. This allows for more flexible and structured categorization of products.
Available taxonomy types are:

- property: product properties like brand, vendor, gtin, ...
- variant property: color, size, ...
- category: product categories like clothing, electronics, ...
- google_category: google product categories like apparel, electronics, ...
- tag: product tags like sale, new, ...

- Bumped: `thinktomorrow/vine` to 0.5.1
- Added: `TaxonNode::getTaxonomyId()` to retrieve the taxonomy id of a taxon.
- Added: `TaxonTreeRepository::getTreeByTaxonomyId(string $taxonomyId)` to retrieve the taxon tree by taxonomy id.
- Changed: `TaxonIdOptionsComposer::getOptions()` hernoemd naar `getTaxaAsOptions(string $taxonomyId)`.
- Changed: `TaxonIdOptionsComposer::getOptionsForMultiselect()` hernoemd naar
  `getTaxaAsOptionsForMultiselect(string $taxonomyId)`.
- Changed: `TaxonIdOptionsComposer::exclude()` hernoemd naar `excludeTaxa(array|string $excludeTaxonIds)`.
- Removed: `TaxonIdOptionsComposer::getRoots()`, `TaxonIdOptionsComposer::includeRoots()` and
  `TaxonIdOptionsComposer::include()`.

- Added: Product::getProductTaxa() and updateProductTaxa(). Also Product::getVariantProperties() as subset of the
  product taxa to allow for specific behavior around the variant properties.
- Added: Variant::getVariantTaxa() and updateVariantTaxa(), Variant::getVariantProperties() as subset of the variant
  taxa.

## 2025-06-02 - 0.7.3

- Added: vat exemption handling for international business orders.

## 2025-03-31 - 0.7.2

- Added: `OrderGridItem::isBusiness()' method to check if the order is a business order.

## 2025-03-31 - 0.7.1

- Added: Vies vat number validation.
- Removed: Old Locale class.

## 2025-02-01 - 0.7.0

#### Vat rates removed from config

Vat rates are no longer kept in config but rather managed via admin. Only thing to determine in config is the primary
vat country.

- Removed from config: `getDefaultTaxRate()` and `getAvailableTaxRates()` methods. Use `getPrimaryVatRate()` and
  `getPrimaryVatCountry()` instead.
- Removed from laravel config file: `'default_tax_rate' => '21',` and `'tax_rates' => ['21', '6', '12']`.
- Added to laravel config file: `'primary_vat_country' => 'BE'`.

Tax is a more generic term and can be used for other types of taxes as well. The vat term is more specific to the vat of
the catalog prices. Therefore, the term `vat` is used in the codebase instead of `tax`. Tax is used in the context of
the checkout and order.

- Renamed Vat methods to better reflect its behavior. `getTaxRate` is now `getVatPercentage`, `getTaxableTotal` is now
  `getVatApplicableTotal`, `getTaxTotal` is now `getVatTotal` and `getPreciseTaxTotal` is now `getPreciseVatTotal`.

- Added two config values to the trader config file: `fallback_standard_vat_rate` and `primary_vat_country`.

## 2024-05-01 - 0.6.6

- Added: availability check for variant. `VariantLink::isVariantAvailable()`.

## 2024-05-01 - 0.6.5

- Added: Verify parsed invoice number is positive integer
- Fixed: In VineTaxonFilterTreeComposer, the subfiltering was not correctly applied. This is now fixed.

## 2024-04-25 - 0.6.4

- Changed: `ProductDetailRepository::findProductDetail` now accepts a second parameter `allowOffline` to also return
  offline variants. This defaults to false.

## 2024-01-22 - 0.6.3

- Fixed: TaxonFilterTreeComposer::getAvailableFilters() filtering improved: For type type of category taxa, only
  children of the given main taxon are returned.

## 2024-01-16 - 0.6.2

- Fixed: Taxon MultiSelect support for chief > 0.8.3

## 2023-03-23 - 0.6.1

- Added: `orderGridRepository::sortByCreatedAt` and `orderGridRepository::sortByCreatedAt`.

## 2023-03-13 - 0.6.0

- Changed: OrderState, ShippingState and PaymentState are now interfaces. Default State classes are provided out of the
  box.
- Added: stock logic
- Added: Payment method logic for add multiple payment options in checkout.
- Added: Payment Method crud application api.
- Added: Two order transitions: mark_order_as_paid and confirm_as_business. The latter allows to process a business
  order without actual payment.
- Added: AdjustLine adjuster to change line quantity, price,... per project based on the given order context.
- Added: Payment and Shipping provider id domain value via a `getProviderId` method. This is used in the project to
  handle each profile/method with proper gateway/provider handling.
- Added: state column to payment methods table. And removed unused 'active' columns. For existing projects, you can use
  the following migrations:

```php
Schema::table('trader_shipping_profiles', function (Blueprint $table) {
    $table->dropColumn('active');
});

Schema::table('trader_payment_methods', function (Blueprint $table) {
    $table->dropColumn('active');
});

Schema::table('trader_shipping_profiles', function (Blueprint $table) {
    $table->string('provider_id');
});

Schema::table('trader_payment_methods', function (Blueprint $table) {
    $table->string('provider_id');
});

Schema::table('trader_payment_methods', function (Blueprint $table) {
    $table->string('state')->default(\Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodState::online->value);
});

Schema::create('trader_order_lines', function (Blueprint $table) {
    $table->boolean('reduced_from_stock')->default(0);
});

Schema::create('trader_product_variants', function (Blueprint $table) {
        $table->integer('stock_level')->default(0);
        $table->boolean('ignore_out_of_stock')->default(true);
        $table->json('stock_data')->nullable();
});

```

- Added: TaxonFilterTreeComposer::getOnlineProductIds(string $taxonId); to collect product ids of online products.
- Fixed: Discount calculation when price calculations is set to 'include_vat_in_prices'
- Changed: TaxonFilterTreeComposer::getActiveFilters now returns filters that have online products
- Changed: LineAdded property productId renamed to variantId. This was actually already the value of the variant id.

## 2022-12-20 - 0.5.7

- Added: extra OrderState::cart_completed state which indicates that order has sufficient data for potential payment and
  fulfillment.
- Added: php8.2 support

## 2022-12-01 - 0.5.6

- Fixed: allow to show localized personalisation label

## 2022-11-17 - 0.5.5

- Added: method to the product gridItem interface `GridItem::getTaxonIds()`. to give all the associated taxon ids of
  this product item.
- Added: `CartLine::getUnitPriceAsMoney()`, `CartLine::getLinePriceAsMoney()`, `CartLine::getLinePriceAsMoney()` and
  `CartLine::getUnitPriceAsPrice`.
- Added: `MerchantOrderLine::getUnitPriceAsMoney()`, `MerchantOrderLine::getLinePriceAsMoney()`,
  `MerchantOrderLine::getLinePriceAsMoney()` and `MerchantOrderLine::getUnitPriceAsPrice`.
- Added: money methods on the MerchantOrder and Cart read model. Also includes_tax is consistently available on these
  methods.

## 2022-11-14 - 0.5.4

- Fixed: TaxonNode label and content when locale was missing returned as array.
- Added: ean as non-required, unique variant column in migration.
- Added: Now both sku and ean can be updated via domain methods `Variant::updateSku` and `Variant::updateEan`.

## 2022-11-08 - 0.5.3

- Fixed: do not record order update events when state hasn't changed.
- Added: ProductDetail::getUnitPriceAsPrice() and ProductDetail::getSalePriceAsPrice() to retrieve the original Price
  objects.

## 2022-11-07 - 0.5.2

- Fixed: localized variant option title returns full array when locale title wasn't present

## 2022-11-07 - 0.5.1

- Added: dataAsPrimitive() helper method to render data and ensure that a primitive is given, else the default is
  returned. The default data() method can also return object or array, which can cause - in case of missing
  translations - unexcepted array returns.

## 2022-11-03 - 0.5.0

First release of the trader package.
