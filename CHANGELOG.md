# Changelog

Important changes will be notified in this file

# 0.3.0

### Adjuster contract - breaking change
- Require Adjuster object as 3rd parameter of Discount and Sale objects. Adjusters are Value Objects and there are currently two: Amount and Percentage. 
- available method `getParameter` on Adjuster or Condition object. This will return the first value or can be specified by key e.g. `getParameter('amount')`.

### Cleanup of Common folder structure with breaking changes
- Removed the `Common\Domain` directory and moved all subdirectories up, including namespaces.
- Removed `Common\Domain\Conditions\OrderCondition` and `Common\Domain\Conditions\ItemCondition` interfaces
- Added `DiscountCondition` interface next to existing `SaleCondition`
- Moved `Common\Domain\Conditions\DiscountCondition` interface to `/Discounts/Conditions/` folder.
- Moved `Common\Domain\Conditions\SaleCondition` interface to `/Sales/Conditions` folder.
- Moved `Common\Domain\Conditions\BaseDiscount` to `/Discounts/Conditions/` folder.
- Renamed and moved `Common\Domain\Conditions\Condition` interface to `Common\Contracts\HasParameters`
- Added `Common\Contracts\HasType` interface
- Added 2 traits for these contracts: `Common\Helpers\HandlesParameters` and `Common\Helpers\HandlesType`
- Moved `Common\Application\ResolvesFromContainer` to `Common\Helpers\ResolvesFromContainer`
- Moved `AbstractPresenter` and `GetDynamicValue` to `Common\Presenters` folder.
- Conditions no longer require nested associative array of parameters. If condition asks for a single
parameter, this can be added as such. 

