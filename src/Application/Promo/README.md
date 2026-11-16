# Promotions and discount combinations

This directory contains the application flow for selecting and applying promotions to a cart. A **promo** is an offer; it can contain one or more **discounts**. Its coupon code determines whether the customer must enter a code or the promo is considered automatically. Separately, a promo can be managed by an admin or created by an integration as a system promo. Neither its application method nor its management type gives it priority.

## Discount types

Each discount declares what it reduces through `DiscountableType`:

| Type | Applies to |
| --- | --- |
| `line` | An eligible product line |
| `order` | The cart subtotal after line discounts |
| `shipping` | Shipping costs |
| `payment` | Payment costs |

The type belongs to the discount; the combination settings belong to the promo. One promo may contain discounts of several types. `DiscountableType` lives in `Domain/Model/Promo/Discounts/DiscountableType.php`.

## Combination rules

A promo's `combines_with` setting lists the **types of other promos** it allows alongside itself. Two promos can combine only if each allows *every type* present in the other promo. This applies across automatic promos, coupons, system promos and admin-created promos alike. A promo with both a line and an order discount is considered as one offer when combinations are selected; its eligible discounts are applied together.

Product discounts on different lines can coexist when their promos mutually permit `line` combinations. When two promos target the **same line**, both must additionally enable `multiple_product_discounts_per_line` to stack there. Otherwise, the selector compares the competing offers and keeps the combination that gives the better cart total. This same-line permission governs discounts from *different* promos; a promo's own eligible discounts belong to its single offer.

There is no `promo_group`: combination rules are defined by discount type and reciprocal permission in `Domain/Model/Promo/CombinationPolicy.php` and `Promo::canCombineWith()`.

### Example

```php
use Thinktomorrow\Trader\Application\Promo\CUD\CreatePromo;
use Thinktomorrow\Trader\Application\Promo\CUD\CreatePromoDiscount;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\DiscountableType;

$promoId = $promoApplication->createPromo(new CreatePromo(
    couponCode: 'WELCOME10',
    startAt: null,
    endAt: null,
    combinesWith: ['line', 'shipping'],
    multipleProductDiscountsPerLine: false,
    data: [],
));

$promoApplication->createPromoDiscount(new CreatePromoDiscount(
    promoId: $promoId->get(),
    key: 'percentage_off',
    conditions: [],
    data: ['percentage' => '10'],
    discountableType: DiscountableType::order,
));
```

This order-discount promo permits line and shipping promos. To combine with it, a line or shipping promo must also allow `order`. For a custom discount, explicitly declare its `DiscountableType` and register its domain and applicable-discount mappings in the corresponding factories.

## Calculation and best-combination selection

Discounts are applied in this order:

1. Line discounts reduce eligible products.
2. Shipping and payment costs are recalculated for the revised cart.
3. Order discounts reduce the subtotal **after** line discounts. Multiple percentage order discounts each use that same subtotal, before any order discount is subtracted: 10% plus 20% on a subtotal of 100 saves 30, not 28. Percentage order discounts apply before fixed-amount order discounts; the latter cannot reduce the remaining subtotal below zero.
4. Shipping discounts apply to shipping costs.
5. Payment discounts apply to payment costs.

When offers conflict, the selector compares valid combinations using the **final payable total including VAT**, not merely the value of each individual discount. It evaluates candidates on isolated order copies and applies only the winner to the real order. At equal totals it prefers more applied discounts; an exact tie follows a deterministic promo-ID order. An entered coupon is a candidate, **not** a priority override. Its code can remain entered when another offer wins; inspect the applied discounts to see what actually reduced the cart.

Currently, mutually combinable **shipping discounts can stack on the same shipping cost**, capped at that cost; payment discounts behave likewise. This is the behavior exercised by `tests/Acceptance/Promo/ServiceDiscountCombinationTest.php`.

## Where to change behavior

- `Cart/RefreshCart/Adjusters/AdjustDiscounts.php` collects available system and automatic promos, plus the entered coupon promo.
- `SelectBestPromoCombination.php` coordinates candidate preparation, selection and final application.
- `SelectBestPromoCombination/PromoCandidates.php` resolves applicable discounts and indexes product-line applicability.
- `SelectBestPromoCombination/FindBestCombination.php` checks compatible promo selections and compares their final totals.
- `SelectBestPromoCombination/ApplyPromoCombination.php` applies discounts in calculation order to trial copies and to the winning cart.
- `Coupon/CouponPromoApplication.php` enters or removes a code and runs the same selection again.
- `CUD/PromoApplication.php` creates and updates promos and their discounts.

For behavior examples, see `tests/Acceptance/Promo/PromoDiscountTest.php`, `tests/Acceptance/Promo/ServiceDiscountCombinationTest.php` and `tests/Unit/Model/CombinationPolicyTest.php`.
