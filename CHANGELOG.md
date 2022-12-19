# Changelog

Important changes will be notified in this file

## unreleased

## 2022-12-19 - 0.5.7
- Added: extra OrderState::cart_completed state which indicates that order has sufficient data for potential payment and fulfillment.

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

