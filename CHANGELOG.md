# Changelog

Important changes will be notified in this file

## unreleased

- Added: Payment method logic for add multiple payment options in checkout.
- Added: Payment Method crud application api.
- Added: Two order transitions: mark_order_as_paid and confirm_as_business. The latter allows to process a business order without actual payment.
- Added: AdjustLine adjuster to change line quantity, price,... per project based on the given order context.
- Added: Payment and Shipping provider id domain value via a `getProviderId` method. This is used in the project to handle each profile/method with proper gateway/provider handling.
- Added: state column to payment methods table. And removed unused 'active' columns. For existing projects, you can use the following migrations:
```php 
Schema::table('trader_shipping_profiles', function (Blueprint $table) {
    $table->dropColumn('active');
});

Schema::table('trader_payment_methods', function (Blueprint $table) {
    $table->dropColumn('active');
});

Schema::table('trader_shipping_profiles', function (Blueprint $table) {
    $table->string('provider');
});

Schema::table('trader_payment_methods', function (Blueprint $table) {
    $table->string('provider');
});

Schema::table('trader_payment_methods', function (Blueprint $table) {
    $table->string('state')->default(\Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodState::online->value);
    $table->boolean('active')->default(1);
});
```
- Added: TaxonFilterTreeComposer::getOnlineProductIds(string $taxonId); to collect product ids of online products.
- Changed: TaxonFilterTreeComposer::getActiveFilters now returns filters that have online products

## 2022-12-20 - 0.5.7
- Added: extra OrderState::cart_completed state which indicates that order has sufficient data for potential payment and fulfillment.
- Added: php8.2 support

## 2022-12-01 - 0.5.6
- Fixed: allow to show localized personalisation label

## 2022-11-17 - 0.5.5
- Added: method to the product gridItem interface `GridItem::getTaxonIds()`. to give all the associated taxon ids of this product item.
- Added: `CartLine::getUnitPriceAsMoney()`, `CartLine::getLinePriceAsMoney()`, `CartLine::getLinePriceAsMoney()` and `CartLine::getUnitPriceAsPrice`.
- Added: `MerchantOrderLine::getUnitPriceAsMoney()`, `MerchantOrderLine::getLinePriceAsMoney()`, `MerchantOrderLine::getLinePriceAsMoney()` and `MerchantOrderLine::getUnitPriceAsPrice`.
- Added: money methods on the MerchantOrder and Cart read model. Also includes_tax is consistently available on these methods.

## 2022-11-14 - 0.5.4
- Fixed: TaxonNode label and content when locale was missing returned as array.
- Added: ean as non-required, unique variant column in migration.
- Added: Now both sku and ean can be updated via domain methods `Variant::updateSku` and `Variant::updateEan`.

## 2022-11-08 - 0.5.3
- Fixed: do not record order update events when state hasn't changed.
- Added: ProductDetail::getUnitPriceAsPrice() and ProductDetail::getSalePriceAsPrice() to retrieve the original Price objects.

## 2022-11-07 - 0.5.2
- Fixed: localized variant option title returns full array when locale title wasn't present

## 2022-11-07 - 0.5.1
- Added: dataAsPrimitive() helper method to render data and ensure that a primitive is given, else the default is returned. The default data() method can also return object or array, which can cause - in case of missing translations - unexcepted array returns.

## 2022-11-03 - 0.5.0
First release of the trader package.

