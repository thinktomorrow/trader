# Changelog

Important changes will be notified in this file

## unreleased

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

